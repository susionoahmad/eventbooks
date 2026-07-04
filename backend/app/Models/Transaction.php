<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory, BelongsToTenant, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'event_id',
        'vendor_id',
        'nomor_transaksi',
        'tanggal',
        'tipe',
        'kategori',
        'deskripsi',
        'nominal',
        'nominal_gross',
        'metode_pembayaran'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:2',
        'nominal_gross' => 'decimal:2',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function taxes()
    {
        return $this->hasMany(Tax::class);
    }

    public function invoicePayments()
    {
        return $this->hasMany(InvoicePayment::class);
    }
}
