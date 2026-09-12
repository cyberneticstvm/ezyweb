<?php

namespace App\Http\Requests;

use App\Builder\SectionRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePageSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'page_id' => ['required', 'integer'],
            'type' => ['required', 'string', Rule::in(app(SectionRegistry::class)->types())],
        ];
    }
}
