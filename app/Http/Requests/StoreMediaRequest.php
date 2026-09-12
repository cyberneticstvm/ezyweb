<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $maxKb = (int) config('media.max_upload_kb', 10240);
        $maxDimension = (int) config('media.max_dimension', 12000);

        return [
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:'.$maxKb, 'dimensions:max_width='.$maxDimension.',max_height='.$maxDimension],
            'folder' => ['nullable', 'string', 'max:100', 'regex:/^[\pL\pN _-]+$/u'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
