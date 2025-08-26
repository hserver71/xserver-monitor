<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vps extends Model
{
    use HasFactory;

    protected $fillable = [
        'line_id',
        'server_id',
        'client_id',
        'name',
        'ip',
        'domains',
        'username',
        'password',
        'linename',
        'serverdomain'
    ];

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function logs()
    {
        return $this->hasMany(Log::class);
    }
}
