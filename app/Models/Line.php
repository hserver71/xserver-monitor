<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Line extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'password',
        'status',
        'client_id',
        'assigned_at'
    ];

    protected $casts = [
        'assigned_at' => 'datetime'
    ];

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function vps()
    {
        return $this->hasMany(Vps::class);
    }
}
