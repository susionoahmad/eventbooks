<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory, BelongsToTenant, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'kode_klien',
        'nama',
        'tipe',
        'perusahaan',
        'npwp',
        'email',
        'telepon',
        'alamat',
        'file_ktp',
        'file_npwp'
    ];

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public static function generateNextCode(int $tenantId): string
    {
        $today = now()->format('Ymd');
        $prefix = $today . '-';

        $lastClient = self::where('tenant_id', $tenantId)
            ->where('kode_klien', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(kode_klien, 10) AS UNSIGNED) DESC')
            ->first();

        $nextNumber = 1;
        if ($lastClient) {
            $lastNumber = (int) substr($lastClient->kode_klien, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        }

        return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
