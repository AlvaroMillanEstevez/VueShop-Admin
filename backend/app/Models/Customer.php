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
        'notes',
    ];

    /**
     * Relationship with orders - Un cliente puede tener muchos pedidos
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Calculate total spent by customer across all orders
     */
    public function getTotalSpentAttribute()
    {
        return $this->orders()->sum('total');
    }

    /**
     * Get orders count
     */
    public function getOrdersCountAttribute()
    {
        return $this->orders()->count();
    }

    /**
     * Get last order date
     */
    public function getLastOrderAtAttribute()
    {
        return $this->orders()->latest()->first()?->created_at;
    }

    /**
     * Scope para búsqueda por nombre o email
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    /**
     * Scope para obtener clientes con pedidos
     */
    public function scopeWithOrders($query)
    {
        return $query->whereHas('orders');
    }

    /**
     * Scope para obtener clientes sin pedidos
     */
    public function scopeWithoutOrders($query)
    {
        return $query->whereDoesntHave('orders');
    }
}