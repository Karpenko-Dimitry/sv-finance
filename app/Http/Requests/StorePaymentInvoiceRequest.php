<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentInvoiceRequest extends FormRequest
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
            'legal_entity' => 'required|string|max:255',
            'address' => 'required|string|max:255',
        ];
    }

    public function attributes()
    {
        return [
            'legal_entity' => trim(trans('telegram.payment_invoice.form.label.legal_entity'), ':'),
            'address' =>  trim(trans('telegram.payment_invoice.form.label.address'), ':'),
        ];
    }
}
