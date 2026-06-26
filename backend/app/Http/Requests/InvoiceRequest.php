<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $tenantId = Auth::user()->tenant_id;

        return [
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where('tenant_id', $tenantId)
            ],
            'event_id' => [
                'required',
                Rule::exists('events', 'id')->where('tenant_id', $tenantId)
            ],
            'nomor_invoice' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('invoices', 'nomor_invoice')
                    ->where('tenant_id', $tenantId)
                    ->ignore($this->route('invoice')?->id)
            ],
            'tanggal' => 'required|date',
            'jatuh_tempo' => 'required|date|after_or_equal:tanggal',
            'jenis_invoice' => 'required|in:dp,termin,pelunasan',
            'subtotal' => 'required|numeric|min:0',
            'apply_ppn' => 'nullable|boolean'
        ];
    }
}
