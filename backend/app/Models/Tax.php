<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'transaction_id',
        'event_id',
        'tipe_pajak',
        'dpp',
        'tarif',
        'nominal_pajak',
        'pihak_terkait_nama',
        'pihak_terkait_npwp',
        'masa_pajak',
        'status'
    ];

    protected $casts = [
        'dpp' => 'decimal:2',
        'tarif' => 'decimal:2',
        'nominal_pajak' => 'decimal:2',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
