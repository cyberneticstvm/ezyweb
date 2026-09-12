<?php

namespace App\Publishing;

use App\Builder\SectionRegistry;
use App\Models\Template;
use App\Models\Website;
use Illuminate\Validation\ValidationException;

class PublishValidator
{
    public function __construct(private readonly SectionRegistry $sections) {}

    /** @param array<string, mixed> $snapshot */
    public function validate(Website $website, array $snapshot): void
    {
        $errors = [];
        $templateExists = Template::query()->where('key', $website->template_key)->where('is_active', true)->exists();
        if (! $templateExists) {
            $errors['template'] = 'Select an active website template before publishing.';
        }

        $pages = is_array($snapshot['pages'] ?? null) ? $snapshot['pages'] : [];
        $enabled = array_values(array_filter($pages, fn (mixed $page): bool => is_array($page) && ($page['is_enabled'] ?? false) === true));
        $home = collect($enabled)->first(fn (array $page): bool => ($page['is_home'] ?? false) === true);
        if ($home === null) {
            $errors['pages'] = 'An enabled home page is required before publishing.';
        } else {
            $homeSections = is_array($home['sections'] ?? null) ? $home['sections'] : [];
            $hasEnabledSection = collect($homeSections)->contains(fn (mixed $section): bool => is_array($section) && ($section['is_enabled'] ?? false) === true);
            if (! $hasEnabledSection) {
                $errors['sections'] = 'The home page needs at least one enabled section.';
            }
        }

        foreach ($enabled as $page) {
            foreach (is_array($page['sections'] ?? null) ? $page['sections'] : [] as $section) {
                if (is_array($section) && ($section['is_enabled'] ?? false) === true && (! is_string($section['type'] ?? null) || ! $this->sections->has($section['type']))) {
                    $errors['sections'] = 'One or more enabled sections are not supported by the selected renderer.';
                    break 2;
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
