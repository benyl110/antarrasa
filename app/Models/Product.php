<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'store_id', 'name', 'description', 'price', 'image_path',
        'stock_qty', 'availability_status',
    ];

    protected function casts(): array
    {
        return ['price' => 'integer', 'stock_qty' => 'integer'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
