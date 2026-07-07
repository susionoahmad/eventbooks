<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventInvitation extends Model
{
    use HasFactory, BelongsToTenant, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'event_id',
        'title',
        'date_time_info',
        'maps_url',
        'is_custom_template',
        'preset_template',
        'template_background',
        'background_color',
        'primary_color',
        'accent_color',
        'text_color',
        'button_text_color',
        'font_family',
    ];

    protected $casts = [
        'is_custom_template' => 'boolean',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
