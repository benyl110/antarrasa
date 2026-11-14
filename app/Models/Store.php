<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $fillable = [
        'user_id', 'store_name', 'description', 'phone', 'address',
        'logo_path', 'bank_name', 'bank_account_name', 'bank_account_number',
        'is_open', 'store_status', 'max_delivery_distance_km',
    ];

    protected function casts(): array
    {
        return ['is_open' => 'boolean', 'max_delivery_distance_km' => 'decimal:1'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
