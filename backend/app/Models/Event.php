<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'nomor_event',
        'nama_event',
        'jenis_event',
        'tanggal_mulai',
        'tanggal_selesai',
        'lokasi',
        'nilai_kontrak',
        'status'
    ];

    protected $casts = [
        'nilai_kontrak' => 'decimal:2',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function rabItems()
    {
        return $this->hasMany(RabItem::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function taxes()
    {
        return $this->hasMany(Tax::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    // Dynamic Financial Calculations
    public function getTotalRabBudgetAttribute(): float
    {
        return (float) $this->rabItems()->sum('subtotal');
    }

    public function getTotalActualCostAttribute(): float
    {
        // Actual cost is calculated as sum of all cash outflows for this event
        return (float) $this->transactions()->where('tipe', 'kas_keluar')->sum('nominal');
    }

    public function getNetProfitAttribute(): float
    {
        // Profit = Contract Value - Actual Cash Outflow
        // Note: Withholding taxes (PPh 21/23) are already part of total_actual_cost as transactions are gross.
        // PPN is a pass-through tax and should not be treated as a direct event expense.
        return $this->nilai_kontrak - $this->total_actual_cost;
    }

    public function getProfitMarginPercentageAttribute(): float
    {
        if ($this->nilai_kontrak <= 0) {
            return 0.00;
        }
        return round(($this->net_profit / $this->nilai_kontrak) * 100, 2);
    }
}
