<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class VendorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode_vendor' => $this->kode_vendor,
            'nama_vendor' => $this->nama_vendor,
            'kategori' => $this->kategori,
            'npwp' => $this->npwp,
            'email' => $this->email,
            'telepon' => $this->telepon,
            'alamat' => $this->alamat,
            'file_ktp' => $this->file_ktp,
            'file_npwp' => $this->file_npwp,
            'file_ktp_url' => $this->file_ktp ? Storage::disk('public')->url($this->file_ktp) : null,
            'file_npwp_url' => $this->file_npwp ? Storage::disk('public')->url($this->file_npwp) : null,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
