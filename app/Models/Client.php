<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip',
        'domain'
    ];

    // Relationships
    public function servers()
    {
        return $this->hasMany(Server::class);
    }

    public function vps()
    {
        return $this->hasMany(Vps::class);
    }

    public function logs()
    {
        return $this->hasMany(Log::class);
    }

    public function lines()
    {
        return $this->hasMany(Line::class);
    }

    // If you have a relationship with load balancers
    public function loadBalancers()
    {
        return $this->hasMany(LoadBalancer::class);
    }
}