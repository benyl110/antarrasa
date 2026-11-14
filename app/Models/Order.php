<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_code', 'checkout_token', 'consumer_id', 'store_id', 'order_status', 'delivery_method',
        'recipient_name', 'recipient_phone', 'delivery_address', 'delivery_distance_km',
        'distance_verified_by', 'distance_verified_at', 'payment_method', 'payment_status',
        'payment_proof_path', 'payment_verified_at', 'payment_rejection_reason',
        'subtotal', 'delivery_fee', 'total', 'cancellation_reason', 'cancellation_requested_at',
        'cancellation_decided_at', 'cancellation_decision', 'cancellation_previous_status',
        'refund_note', 'driver_name', 'driver_phone', 'driver_cost', 'driver_booking_status',
        'notes', 'stock_reserved',
    ];

    protected function casts(): array
    {
        return [
            'delivery_distance_km' => 'decimal:2',
            'distance_verified_at' => 'datetime',
            'payment_verified_at' => 'datetime',
            'cancellation_requested_at' => 'datetime',
            'cancellation_decided_at' => 'datetime',
            'subtotal' => 'integer',
            'delivery_fee' => 'integer',
            'total' => 'integer',
            'driver_cost' => 'integer',
            'stock_reserved' => 'boolean',
        ];
    }

    public function consumer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consumer_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
