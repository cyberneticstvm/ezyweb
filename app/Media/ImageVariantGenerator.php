<?php

namespace App\Media;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ImageVariantGenerator
{
    /** @return array{width: int, height: int, source_mime: string, checksum: string, variants: array<string, array{bytes: string, width: int, height: int, size: int}>} */
    public function generate(UploadedFile $file): array
    {
        $contents = file_get_contents($file->getRealPath());
        if (! is_string($contents) || $contents === '') {
            throw ValidationException::withMessages(['image' => 'The uploaded image could not be read.']);
        }
        $info = @getimagesizefromstring($contents);
        $image = @imagecreatefromstring($contents);
        if ($info === false || ! $image instanceof GdImage) {
            throw ValidationException::withMessages(['image' => 'Only valid JPEG, PNG, or WebP images are accepted.']);
        }
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (! in_array($info['mime'], $allowed, true)) {
            imagedestroy($image);
            throw ValidationException::withMessages(['image' => 'This image format is not supported.']);
        }

        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);
        $variants = [];
        foreach ((array) config('media.variants') as $name => $maxWidth) {
            $width = min($sourceWidth, (int) $maxWidth);
            $height = max(1, (int) round($sourceHeight * ($width / $sourceWidth)));
            $canvas = imagecreatetruecolor($width, $height);
            if (! $canvas instanceof GdImage) {
                imagedestroy($image);
                throw ValidationException::withMessages(['image' => 'The image could not be optimized.']);
            }
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $transparent);
            imagecopyresampled($canvas, $image, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
            ob_start();
            $written = imagewebp($canvas, null, (int) config('media.quality', 84));
            $bytes = ob_get_clean();
            imagedestroy($canvas);
            if (! $written || ! is_string($bytes)) {
                imagedestroy($image);
                throw ValidationException::withMessages(['image' => 'The optimized image could not be encoded.']);
            }
            $variants[(string) $name] = ['bytes' => $bytes, 'width' => $width, 'height' => $height, 'size' => strlen($bytes)];
        }
        imagedestroy($image);

        return ['width' => $sourceWidth, 'height' => $sourceHeight, 'source_mime' => (string) $info['mime'], 'checksum' => hash('sha256', $contents), 'variants' => $variants];
    }
}
