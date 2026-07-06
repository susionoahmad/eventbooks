<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode_klien' => $this->kode_klien,
            'nama' => $this->nama,
            'tipe' => $this->tipe,
            'perusahaan' => $this->perusahaan,
            'npwp' => $this->npwp,
            'email' => $this->email,
            'telepon' => $this->telepon,
            'alamat' => $this->alamat,
            'file_ktp' => $this->file_ktp,
            'file_npwp' => $this->file_npwp,
            'file_ktp_url' => $this->file_ktp ? "/clients/{$this->id}/ktp" : null,
            'file_npwp_url' => $this->file_npwp ? "/clients/{$this->id}/npwp" : null,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
