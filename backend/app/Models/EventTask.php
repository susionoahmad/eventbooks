<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventTask extends Model
{
    use HasFactory, BelongsToTenant, LogsActivity;

    protected $table = 'event_tasks';

    protected $fillable = [
        'tenant_id',
        'event_id',
        'nama_task',
        'pic',
        'target_date',
        'status',
        'keterangan'
    ];

    protected $casts = [
        'target_date' => 'date',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
