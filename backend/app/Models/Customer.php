<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'last_order_at',
        'total_spent',
    ];

    protected $casts = [
        'last_order_at' => 'datetime',
        'total_spent' => 'decimal:2',
    ];

    // Relación con Orders
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Clientes VIP (más de 2000€ gastados)
    public function scopeVip($query)
    {
        return $query->where('total_spent', '>', 2000);
    }

    // Clientes recientes (pedido en los últimos 7 días)
    public function scopeRecentActivity($query)
    {
        return $query->where('last_order_at', '>=', now()->subDays(7));
    }
}