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
     * Relationship with orders - A customer can have many orders.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Calculate total amount spent by the customer across all orders.
     */
    public function getTotalSpentAttribute()
    {
        return $this->orders()->sum('total');
    }

    /**
     * Get the number of orders.
     */
    public function getOrdersCountAttribute()
    {
        return $this->orders()->count();
    }

    /**
     * Get the date of the most recent order.
     */
    public function getLastOrderAtAttribute()
    {
        return $this->orders()->latest()->first()?->created_at;
    }

    /**
     * Scope to search by name, email, or phone.
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
     * Scope to get customers with orders.
     */
    public function scopeWithOrders($query)
    {
        return $query->whereHas('orders');
    }

    /**
     * Scope to get customers without orders.
     */
    public function scopeWithoutOrders($query)
    {
        return $query->whereDoesntHave('orders');
    }
}
