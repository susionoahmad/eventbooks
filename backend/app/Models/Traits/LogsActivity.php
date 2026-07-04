<?php

namespace App\Models\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            static::logActivity('created', $model);
        });

        static::updated(function ($model) {
            static::logActivity('updated', $model);
        });

        static::deleted(function ($model) {
            static::logActivity('deleted', $model);
        });
    }

    protected static function logActivity(string $event, $model): void
    {
        if (!Auth::hasUser()) {
            return;
        }

        $user = Auth::user();
        $modelName = class_basename($model);
        
        $identifier = static::getModelIdentifier($model);
        
        $eventLabel = [
            'created' => 'membuat',
            'updated' => 'memperbarui',
            'deleted' => 'menghapus',
        ][$event] ?? $event;

        $description = sprintf(
            'User %s %s %s: "%s"',
            $user->name,
            $eventLabel,
            $modelName,
            $identifier
        );

        $metadata = [];
        if ($event === 'updated') {
            $original = $model->getRawOriginal();
            $dirty = $model->getDirty();
            
            $changes = [];
            foreach ($dirty as $key => $value) {
                if (in_array($key, ['password', 'remember_token', 'updated_at', 'created_at'])) {
                    continue;
                }
                
                $changes[$key] = [
                    'old' => $original[$key] ?? null,
                    'new' => $value
                ];
            }
            
            if (empty($changes)) {
                return;
            }
            
            $metadata['changed_fields'] = $changes;
        }

        AuditLog::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'activity' => ucfirst($event) . ' ' . $modelName,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'metadata' => !empty($metadata) ? $metadata : null,
        ]);
    }

    protected static function getModelIdentifier($model): string
    {
        if (isset($model->nama)) return $model->nama;
        if (isset($model->nama_vendor)) return $model->nama_vendor;
        if (isset($model->nama_event)) return $model->nama_event;
        if (isset($model->nama_barang)) return $model->nama_barang;
        if (isset($model->nama_task)) return $model->nama_task;
        if (isset($model->nama_dokumen)) return $model->nama_dokumen;
        if (isset($model->nomor_invoice)) return $model->nomor_invoice;
        if (isset($model->nomor_transaksi)) return $model->nomor_transaksi;
        if (isset($model->kode_klien)) return $model->kode_klien;
        if (isset($model->kode_vendor)) return $model->kode_vendor;
        if (isset($model->jenis_pajak)) return $model->jenis_pajak . ' (' . ($model->masa_pajak ?? '') . ')';
        if (isset($model->tipe_pajak)) return $model->tipe_pajak . ' (' . ($model->masa_pajak ?? '') . ')';
        if (isset($model->name)) return $model->name;
        if (isset($model->description)) return $model->description;
        if (isset($model->deskripsi)) return $model->deskripsi;
        
        return (string) ($model->id ?? 'N/A');
    }
}
