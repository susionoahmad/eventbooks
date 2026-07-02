<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipe_pajak' => $this->tipe_pajak,
            'dpp' => (float) $this->dpp,
            'tarif' => (float) $this->tarif,
            'nominal_pajak' => (float) $this->nominal_pajak,
            'pihak_terkait_nama' => $this->pihak_terkait_nama,
            'pihak_terkait_npwp' => $this->pihak_terkait_npwp,
            'nomor_bukti_potong' => $this->nomor_bukti_potong,
            'nomor_faktur_pajak' => $this->nomor_faktur_pajak,
            'kode_objek_pajak' => $this->kode_objek_pajak,
            'masa_pajak' => $this->masa_pajak,
            'status' => $this->status,
            'transaction' => [
                'id' => $this->transaction->id ?? null,
                'nomor_transaksi' => $this->transaction->nomor_transaksi ?? null,
                'nominal' => $this->transaction ? (float) $this->transaction->nominal : null,
            ],
            'event' => [
                'id' => $this->event->id ?? null,
                'nomor_event' => $this->event->nomor_event ?? null,
                'nama_event' => $this->event->nama_event ?? null,
            ],
            'invoice' => [
                'id' => $this->invoice->id ?? null,
                'nomor_invoice' => $this->invoice->nomor_invoice ?? null,
                'jenis_invoice' => $this->invoice->jenis_invoice ?? null,
                'nomor_faktur_pajak' => $this->invoice->nomor_faktur_pajak ?? null,
            ],
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
