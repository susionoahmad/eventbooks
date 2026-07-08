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
        'maps_btn_top',
        'maps_btn_left',
        'maps_btn_width',
        'maps_btn_text',
        'maps_btn_height',
    ];

    protected $casts = [
        'is_custom_template' => 'boolean',
        'maps_btn_top' => 'float',
        'maps_btn_left' => 'float',
        'maps_btn_width' => 'float',
        'maps_btn_height' => 'float',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
