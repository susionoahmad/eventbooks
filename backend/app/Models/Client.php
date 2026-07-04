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
        'perusahaan',
        'npwp',
        'email',
        'telepon',
        'alamat'
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
        $lastClient = self::where('tenant_id', $tenantId)
            ->where('kode_klien', 'like', 'CLI-%')
            ->orderByRaw('CAST(SUBSTRING(kode_klien, 5) AS UNSIGNED) DESC')
            ->first();

        $nextNumber = 1;
        if ($lastClient) {
            $lastNumber = (int) substr($lastClient->kode_klien, 4);
            $nextNumber = $lastNumber + 1;
        }

        return 'CLI-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
