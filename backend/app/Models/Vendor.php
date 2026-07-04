<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory, BelongsToTenant, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'kode_vendor',
        'nama_vendor',
        'kategori',
        'npwp',
        'email',
        'telepon',
        'alamat',
        'file_ktp',
        'file_npwp'
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public static function generateNextCode(int $tenantId): string
    {
        $lastVendor = self::where('tenant_id', $tenantId)
            ->where('kode_vendor', 'like', 'VND-%')
            ->orderByRaw('CAST(SUBSTRING(kode_vendor, 5) AS UNSIGNED) DESC')
            ->first();

        $nextNumber = 1;
        if ($lastVendor) {
            $lastNumber = (int) substr($lastVendor->kode_vendor, 4);
            $nextNumber = $lastNumber + 1;
        }

        return 'VND-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
