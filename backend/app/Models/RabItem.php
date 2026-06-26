<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RabItem extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'rab_items';

    protected $fillable = [
        'tenant_id',
        'event_id',
        'kategori',
        'deskripsi',
        'qty',
        'harga'
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'harga' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function getAktualTerbayarAttribute(): float
    {
        // Normalize the RAB item category to lowercase with underscores for comparison
        $kategoriNormalized = strtolower(str_replace([' ', '/'], ['_', ''], trim($this->kategori)));
        // Remove multiple consecutive underscores
        $kategoriNormalized = preg_replace('/_+/', '_', $kategoriNormalized);
        $kategoriNormalized = trim($kategoriNormalized, '_');

        // Get direct transactions of this category (where vendor_id is null)
        // Match if the transaction's kategori matches the RAB normalized category
        $directSum = (float) Transaction::where('event_id', $this->event_id)
            ->where('tipe', 'kas_keluar')
            ->whereNull('vendor_id')
            ->where(function ($q) use ($kategoriNormalized) {
                $q->whereRaw('LOWER(REPLACE(kategori, " ", "_")) = ?', [$kategoriNormalized]);
            })
            ->sum('nominal');

        // Get vendor payments: match where the vendor's kategori is a substring of the RAB kategori
        // OR where the RAB kategori is a substring of the vendor's kategori.
        // This handles cases like RAB "MC / Host" matching vendor kategori "mc"
        $vendorSum = (float) Transaction::where('event_id', $this->event_id)
            ->where('tipe', 'kas_keluar')
            ->whereNotNull('vendor_id')
            ->whereHas('vendor', function ($query) use ($kategoriNormalized) {
                $query->where(function ($q) use ($kategoriNormalized) {
                    // vendor.kategori appears in the RAB category (e.g. "mc" in "mc_/_host")
                    $q->whereRaw('LOCATE(LOWER(kategori), ?) > 0', [$kategoriNormalized])
                      // OR RAB category exactly matches vendor kategori
                      ->orWhereRaw('LOWER(REPLACE(kategori, " ", "_")) = ?', [$kategoriNormalized]);
                });
            })
            ->sum('nominal');

        return $directSum + $vendorSum;
    }
}
