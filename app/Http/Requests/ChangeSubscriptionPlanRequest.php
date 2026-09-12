<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    /** @return array<string, mixed> */
    public function rules(): array { return ['plan_id' => ['required', 'integer', 'exists:plans,id']]; }
}
