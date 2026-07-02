<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nomor_invoice' => $this->nomor_invoice,
            'tanggal' => $this->tanggal->format('Y-m-d'),
            'jatuh_tempo' => $this->jatuh_tempo->format('Y-m-d'),
            'jenis_invoice' => $this->jenis_invoice,
            'subtotal' => (float) $this->subtotal,
            'ppn' => (float) $this->ppn,
            'nomor_faktur_pajak' => $this->nomor_faktur_pajak,
            'total' => (float) $this->total,
            'status' => $this->status,
            'paid_amount' => $this->paid_amount,
            'outstanding_amount' => $this->outstanding_amount,
            'client' => [
                'id' => $this->client->id ?? null,
                'nama' => $this->client->nama ?? null,
                'perusahaan' => $this->client->perusahaan ?? null,
            ],
            'event' => [
                'id' => $this->event->id ?? null,
                'nomor_event' => $this->event->nomor_event ?? null,
                'nama_event' => $this->event->nama_event ?? null,
            ],
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
