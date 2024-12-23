<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReturnForeignCurrencyRevenueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'beneficiary_name' => 'required|string|max:255',
            'beneficiary_address' => 'required|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'beneficiary_name' => trans('telegram.return_foreign_currency_revenue.form.label.beneficiary_name'),
            'beneficiary_address' => trans('telegram.return_foreign_currency_revenue.form.label.beneficiary_address'),
        ];
    }
}
