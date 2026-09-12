<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $definitions = config('plan-features', []);
        $definitions = is_array($definitions) ? $definitions : [];
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'annual_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'currency' => ['required', 'string', 'size:3', 'alpha:ascii'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'grace_days' => ['required', 'integer', 'min:0', 'max:365'],
            'retention_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],
            'features' => ['required', 'array:'.implode(',', array_keys($definitions))],
        ];

        foreach ($definitions as $key => $definition) {
            $definition = is_array($definition) ? $definition : [];
            $rules['features.'.$key] = ($definition['type'] ?? null) === 'boolean'
                ? ['required', 'boolean']
                : ['required', 'integer', 'min:0', 'max:'.(int) ($definition['max'] ?? PHP_INT_MAX)];
        }

        return $rules;
    }
}
