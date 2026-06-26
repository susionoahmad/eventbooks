<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class VendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('kategori')) {
            $kategori = strtolower(trim($this->input('kategori')));
            
            // Map common labels/variations to the DB enum values
            $map = [
                'sound system' => 'sound_system',
                'mc / host' => 'mc',
                'mc/host' => 'mc',
            ];
            
            if (isset($map[$kategori])) {
                $kategori = $map[$kategori];
            } else {
                $kategori = str_replace(' ', '_', $kategori);
            }
            
            $this->merge([
                'kategori' => $kategori,
            ]);
        }
    }

    public function rules(): array
    {
        $tenantId = Auth::user()->tenant_id;
        $vendorId = $this->route('vendor')?->id;

        return [
            'kode_vendor' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vendors', 'kode_vendor')
                    ->where('tenant_id', $tenantId)
                    ->ignore($vendorId)
            ],
            'nama_vendor' => 'required|string|max:255',
            'kategori' => [
                'required',
                Rule::in(['dekorasi', 'sound_system', 'lighting', 'catering', 'venue', 'talent', 'mc', 'dokumentasi', 'transportasi', 'lainnya'])
            ],
            'npwp' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'telepon' => 'nullable|string|max:50',
            'alamat' => 'nullable|string'
        ];
    }
}
