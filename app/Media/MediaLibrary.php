<?php

namespace App\Media;

use App\Models\MediaAsset;
use App\Models\User;
use App\Models\Website;
use App\Tenancy\TenantScope;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;
use App\Subscriptions\FeatureGate;

class MediaLibrary
{
    public function __construct(private readonly ImageVariantGenerator $generator, private readonly FeatureGate $features) {}

    /** @param array<string, mixed> $metadata */
    public function store(Website $website, User $user, UploadedFile $file, array $metadata): MediaAsset
    {
        $generated = $this->generator->generate($file);
        $total = (int) collect($generated['variants'])->sum('size');
        $used = (int) MediaAsset::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->sum('total_size');
        $limit = $this->features->limit($website, 'storage_mb', (int) config('media.storage_limit_mb', 1024)) * 1024 * 1024;
        if ($used + $total > $limit) {
            throw ValidationException::withMessages(['image' => 'This website has reached its media storage limit.']);
        }

        $publicId = (string) Str::ulid();
        $disk = (string) config('media.disk', 'local');
        $prefix = 'tenants/'.$website->tenant_id.'/websites/'.$website->getKey().'/media/'.$publicId;
        $stored = [];
        $variantMetadata = [];
        try {
            foreach ($generated['variants'] as $name => $variant) {
                $path = $prefix.'/'.$name.'.webp';
                if (! Storage::disk($disk)->put($path, $variant['bytes'], ['visibility' => 'private'])) {
                    throw ValidationException::withMessages(['image' => 'The optimized image could not be stored.']);
                }
                $stored[] = $path;
                $variantMetadata[$name] = [
                    'path' => $path, 'width' => $variant['width'], 'height' => $variant['height'], 'size' => $variant['size'],
                    'url' => url('/media/'.$publicId.'/'.$name),
                ];
            }
            $original = $variantMetadata['original'];
            $safeOriginal = Str::limit(preg_replace('/[^\pL\pN._ -]+/u', '-', basename($file->getClientOriginalName())) ?: 'image', 255, '');
            $title = $metadata['title'] ?? pathinfo($safeOriginal, PATHINFO_FILENAME);

            return DB::transaction(fn (): MediaAsset => MediaAsset::query()->create([
                'public_id' => $publicId, 'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'uploaded_by' => $user->getKey(),
                'disk' => $disk, 'path' => $original['path'], 'original_name' => $safeOriginal, 'source_mime_type' => $generated['source_mime'],
                'mime_type' => 'image/webp', 'extension' => 'webp', 'size' => $original['size'], 'total_size' => $total,
                'width' => $original['width'], 'height' => $original['height'], 'folder' => $this->plain($metadata['folder'] ?? null),
                'alt_text' => $this->plain($metadata['alt_text'] ?? null), 'title' => $this->plain($title), 'caption' => $this->plain($metadata['caption'] ?? null),
                'variants' => $variantMetadata, 'checksum' => $generated['checksum'],
            ]), 3);
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($stored);
            throw $exception;
        }
    }

    public function find(Website $website, string $publicId): MediaAsset
    {
        return MediaAsset::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->where('public_id', $publicId)->firstOrFail();
    }

    public function delete(MediaAsset $asset, bool $confirmedUsage): void
    {
        if ($asset->usages()->exists() && ! $confirmedUsage) {
            throw ValidationException::withMessages(['media' => 'This image is in use. Confirm deletion to remove it.']);
        }
        $paths = array_values(array_filter(array_column($asset->variants, 'path'), is_string(...)));
        Storage::disk($asset->disk)->delete($paths);
        $asset->delete();
    }

    private function plain(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim(strip_tags($value));

        return $value === '' ? null : $value;
    }
}
