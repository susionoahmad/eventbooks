<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nomor_event' => $this->nomor_event,
            'nama_event' => $this->nama_event,
            'jenis_event' => $this->jenis_event,
            'tanggal_mulai' => $this->tanggal_mulai->format('Y-m-d'),
            'tanggal_selesai' => $this->tanggal_selesai->format('Y-m-d'),
            'lokasi' => $this->lokasi,
            'nilai_kontrak' => (float) $this->nilai_kontrak,
            'sisa_nilai_kontrak' => (float) ($this->nilai_kontrak - $this->invoices()->sum('subtotal')),
            'status' => $this->status,
            'total_anggaran_rab' => $this->total_rab_budget,
            'realisasi_biaya_aktual' => $this->total_actual_cost,
            'laba_bersih' => $this->net_profit,
            'margin_persentase' => $this->profit_margin_percentage,
            'client' => [
                'id' => $this->client->id ?? null,
                'kode_klien' => $this->client->kode_klien ?? null,
                'nama' => $this->client->nama ?? null,
                'perusahaan' => $this->client->perusahaan ?? null,
                'npwp' => $this->client->npwp ?? null,
                'email' => $this->client->email ?? null,
                'telepon' => $this->client->telepon ?? null,
            ],
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
