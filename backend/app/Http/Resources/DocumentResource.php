<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_dokumen' => $this->nama_dokumen,
            'tipe_dokumen' => $this->tipe_dokumen,
            'file_path' => $this->file_path,
            'file_size' => $this->file_size,
            'file_type' => $this->file_type,
            'uploaded_by' => $this->uploader->name ?? null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
