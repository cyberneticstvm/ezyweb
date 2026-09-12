<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExtendSubscriptionRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    /** @return array<string, mixed> */
    public function rules(): array { return ['days' => ['required', 'integer', 'min:1', 'max:730']]; }
}
