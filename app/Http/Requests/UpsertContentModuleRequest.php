<?php

namespace App\Http\Requests;

use App\Builder\BuilderWorkspace;
use App\Content\ContentModuleRegistry;
use Illuminate\Foundation\Http\FormRequest;

class UpsertContentModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $website = app(BuilderWorkspace::class)->website();
        $entry = $this->route('entry');

        return app(ContentModuleRegistry::class)->rules(
            (string) $this->route('module'),
            (int) $website->getKey(),
            is_numeric($entry) ? (int) $entry : null,
        );
    }
}
