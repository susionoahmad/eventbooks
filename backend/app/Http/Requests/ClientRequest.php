<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $tenantId = Auth::user()->tenant_id;
        $clientId = $this->route('client')?->id;

        return [
            'kode_klien' => [
                'required',
                'string',
                'max:50',
                Rule::unique('clients', 'kode_klien')
                    ->where('tenant_id', $tenantId)
                    ->ignore($clientId)
            ],
            'nama' => 'required|string|max:255',
            'tipe' => 'required|in:perorangan,non_perorangan',
            'perusahaan' => 'nullable|string|max:255',
            'npwp' => $this->input('tipe') === 'non_perorangan' ? 'required|string|max:30' : 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'telepon' => 'nullable|string|max:50',
            'alamat' => 'nullable|string',
            'file_ktp' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'file_npwp' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120'
        ];
    }

    public function messages(): array
    {
        return [
            'npwp.required' => 'NPWP wajib diisi untuk klien Non-Perorangan.',
        ];
    }
}
