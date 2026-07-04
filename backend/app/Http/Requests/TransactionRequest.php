<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class TransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $tenantId = Auth::user()->tenant_id;

        return [
            'tanggal' => 'required|date',
            'tipe' => 'required|in:kas_masuk,kas_keluar',
            'kategori' => [
                'required',
                Rule::in([
                    'dp_event', 'pelunasan_event', 'pendapatan_lain',
                    'pembayaran_vendor', 'transportasi', 'konsumsi', 'operasional', 'marketing', 'pajak'
                ])
            ],
            'sub_kategori' => 'nullable|string|max:100',
            'event_id' => [
                'nullable',
                Rule::exists('events', 'id')->where('tenant_id', $tenantId)
            ],
            'vendor_id' => [
                'nullable',
                Rule::exists('vendors', 'id')->where('tenant_id', $tenantId)
            ],
            'deskripsi' => 'required|string',
            'nominal' => 'required|numeric|min:0.01',
            'nominal_gross' => 'nullable|numeric|min:0',
            'metode_pembayaran' => 'required|in:cash,transfer_bank,card,e_wallet',
            
            // Tax Calculation parameters
            'calculate_pph23' => 'nullable|boolean',
            'calculate_pph21' => 'nullable|boolean',
            'calculate_ppn_masukan' => 'nullable|boolean',
            'pihak_terkait_nama' => 'nullable|string|max:255',
            'pihak_terkait_npwp' => 'nullable|string|max:30',
            'nomor_bukti_potong' => 'nullable|string|max:50',
            'nomor_faktur_pajak' => 'nullable|string|max:50',
            'kode_objek_pajak' => 'nullable|string|max:20'
        ];
    }
}
