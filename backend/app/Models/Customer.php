<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'notes',
    ];

    /**
     * Relationship with user (owner)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with orders
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Scope para filtrar por usuario actual
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Calculate total spent by customer
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
}