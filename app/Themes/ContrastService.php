<?php

namespace App\Themes;

class ContrastService
{
    /** @param array<string, string> $tokens
     * @return array<int, array{pair: string, ratio: float, required: float, passes: bool}>
     */
    public function analyze(array $tokens): array
    {
        return [
            $this->result('Body text / background', $tokens['text'], $tokens['background']),
            $this->result('Heading / background', $tokens['heading'], $tokens['background']),
            $this->result('Primary button', $tokens['primary_foreground'], $tokens['primary']),
            $this->result('Accent button', $tokens['accent_foreground'], $tokens['accent']),
        ];
    }

    public function ratio(string $foreground, string $background): float
    {
        $lighter = max($this->luminance($foreground), $this->luminance($background));
        $darker = min($this->luminance($foreground), $this->luminance($background));

        return round(($lighter + 0.05) / ($darker + 0.05), 2);
    }

    /** @return array{pair: string, ratio: float, required: float, passes: bool} */
    private function result(string $pair, string $foreground, string $background): array
    {
        $ratio = $this->ratio($foreground, $background);

        return ['pair' => $pair, 'ratio' => $ratio, 'required' => 4.5, 'passes' => $ratio >= 4.5];
    }

    private function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        $channels = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
        $linear = array_map(static function (int $channel): float {
            $value = $channel / 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, $channels);

        return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
    }
}
