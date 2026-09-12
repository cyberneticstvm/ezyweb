<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderPageSectionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'page_id' => ['required', 'integer'],
            'section_ids' => ['required', 'array'],
            'section_ids.*' => ['required', 'integer', 'distinct'],
        ];
    }
}
