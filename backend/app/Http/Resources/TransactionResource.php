<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nomor_transaksi' => $this->nomor_transaksi,
            'tanggal' => $this->tanggal->format('Y-m-d'),
            'tipe' => $this->tipe,
            'kategori' => $this->kategori,
            'sub_kategori' => $this->sub_kategori,
            'deskripsi' => $this->deskripsi,
            'nominal' => (float) $this->nominal,
            'nominal_gross' => (float) ($this->nominal_gross ?? $this->nominal),
            'metode_pembayaran' => $this->metode_pembayaran,
            'event' => [
                'id' => $this->event->id ?? null,
                'nomor_event' => $this->event->nomor_event ?? null,
                'nama_event' => $this->event->nama_event ?? null,
            ],
            'vendor' => [
                'id' => $this->vendor->id ?? null,
                'nama_vendor' => $this->vendor->nama_vendor ?? null,
            ],
            'dokumen_pendukung' => $this->dokumen_pendukung,
            'dokumen_pendukung_url' => $this->dokumen_pendukung ? "/transactions/{$this->id}/dokumen" : null,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
