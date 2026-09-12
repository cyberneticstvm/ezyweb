<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SwitchTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'template_key' => ['required', 'string', 'exists:templates,key,is_active,1'],
            'confirmed' => ['accepted'],
        ];
    }
}
