<?php

namespace App\Http\Requests;

use App\Enums\TenantStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(TenantStatus::class)]];
    }
}
