<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordSubscriptionPaymentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'], 'currency' => ['required', 'string', 'size:3'], 'transaction_reference' => ['required', 'string', 'max:255', 'unique:subscription_payments,transaction_reference'], 'paid_at' => ['required', 'date'], 'extend_days' => ['required', 'integer', 'min:1', 'max:730']];
    }
}
