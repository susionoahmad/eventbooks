<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'event_id',
        'nomor_invoice',
        'tanggal',
        'jatuh_tempo',
        'jenis_invoice',
        'subtotal',
        'ppn',
        'nomor_faktur_pajak',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jatuh_tempo' => 'date',
        'subtotal' => 'decimal:2',
        'ppn' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->payments()->sum('nominal');
    }

    public function getOutstandingAmountAttribute(): float
    {
        return $this->total - $this->paid_amount;
    }

    public static function generateNextInvoiceNumber(int $tenantId, string $dateString): string
    {
        $date = \Carbon\Carbon::parse($dateString);
        $yearMonth = $date->format('Ym');
        
        $lastInvoice = self::where('tenant_id', $tenantId)
            ->where('nomor_invoice', 'like', "INV-{$yearMonth}-%")
            ->orderBy('nomor_invoice', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->nomor_invoice, -4);
            $nextNumber = $lastNumber + 1;
        }

        return "INV-{$yearMonth}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
