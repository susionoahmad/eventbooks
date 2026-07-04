<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasFactory, BelongsToTenant, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'transaction_id',
        'payment_transaction_id',
        'event_id',
        'invoice_id',
        'tipe_pajak',
        'dpp',
        'tarif',
        'nominal_pajak',
        'pihak_terkait_nama',
        'pihak_terkait_npwp',
        'nomor_bukti_potong',
        'nomor_faktur_pajak',
        'kode_objek_pajak',
        'file_arsip',
        'nama_file_arsip',
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

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function paymentTransaction()
    {
        return $this->belongsTo(Transaction::class, 'payment_transaction_id');
    }
}
