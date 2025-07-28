<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'status',
        'subtotal',
        'tax',
        'shipping',
        'total',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'total' => 'decimal:2',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    // Relación con Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Relación con OrderItems
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Órdenes del último mes
    public function scopeLastMonth($query)
    {
        return $query->whereBetween('created_at', [
            now()->subMonth()->startOfMonth(),
            now()->subMonth()->endOfMonth()
        ]);
    }

    // Órdenes de este mes
    public function scopeThisMonth($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfMonth(),
            now()->endOfMonth()
        ]);
    }

    // Ventas por día para gráficos
    public function scopeDailySales($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days))
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total) as revenue')
                    ->groupBy('date')
                    ->orderBy('date');
    }
}
