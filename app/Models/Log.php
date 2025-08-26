<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $table = 'logs';

    protected $fillable = [
        'line_id',
        'client_id',
        'vps_id',
        'line_name',
        'client_domain',
        'uptime_status',
        'check_details',
        'admin_notes',
        'checked_at'
    ];

    protected $casts = [
        'uptime_status' => 'boolean',
        'checked_at' => 'datetime'
    ];

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function vps()
    {
        return $this->belongsTo(Vps::class);
    }
} 