<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'npwp',
        'email',
        'telepon',
        'alamat',
        'website',
        'kota',
        'provinsi',
        'kode_pos',
        'jenis_usaha',
        'is_setup_complete',
        'subscription_plan',
        'trial_ends_at',
    ];

    protected $casts = [
        'is_setup_complete' => 'boolean',
        'trial_ends_at'     => 'datetime',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
