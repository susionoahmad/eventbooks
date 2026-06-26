<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RabItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'kategori' => $this->kategori,
            'deskripsi' => $this->deskripsi,
            'qty' => (float) $this->qty,
            'harga' => (float) $this->harga,
            'subtotal' => (float) $this->subtotal,
            'aktual_terbayar' => $this->aktual_terbayar,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
