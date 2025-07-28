<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'sku',
        'category',
        'image_url',
        'active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'active' => 'boolean',
    ];

    // Relación con OrderItems
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Productos más vendidos
    public function scopeTopSelling($query, $limit = 5)
    {
        return $query->withCount(['orderItems as total_sold' => function($query) {
            $query->selectRaw('sum(quantity)');
        }])->orderBy('total_sold', 'desc')->limit($limit);
    }

    // Productos con bajo stock
    public function scopeLowStock($query, $threshold = 10)
    {
        return $query->where('stock', '<=', $threshold)->where('active', true);
    }
}
