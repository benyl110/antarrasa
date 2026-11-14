<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('store_name');
            $table->text('description')->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number', 100)->nullable();
            $table->boolean('is_open')->default(false);
            $table->string('store_status', 20)->default('pending')->index();
            $table->decimal('max_delivery_distance_km', 4, 1)->default(5);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price');
            $table->string('image_path')->nullable();
            $table->unsignedInteger('stock_qty')->default(0);
            $table->string('availability_status', 20)->default('available')->index();
            $table->timestamps();
            $table->index(['store_id', 'availability_status']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 32)->unique();
            $table->foreignId('consumer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->string('order_status', 40)->index();
            $table->string('delivery_method', 20);
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone', 30)->nullable();
            $table->text('delivery_address')->nullable();
            $table->decimal('delivery_distance_km', 5, 2)->nullable();
            $table->foreignId('distance_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('distance_verified_at')->nullable();
            $table->string('payment_method', 20);
            $table->string('payment_status', 30)->default('unpaid')->index();
            $table->string('payment_proof_path')->nullable();
            $table->timestamp('payment_verified_at')->nullable();
            $table->text('payment_rejection_reason')->nullable();
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('delivery_fee')->default(0);
            $table->unsignedBigInteger('total');
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancellation_requested_at')->nullable();
            $table->timestamp('cancellation_decided_at')->nullable();
            $table->string('cancellation_decision', 20)->nullable();
            $table->string('cancellation_previous_status', 40)->nullable();
            $table->text('refund_note')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone', 30)->nullable();
            $table->unsignedBigInteger('driver_cost')->nullable();
            $table->string('driver_booking_status', 20)->default('not_booked');
            $table->text('notes')->nullable();
            $table->boolean('stock_reserved')->default(false);
            $table->timestamps();
            $table->index(['store_id', 'order_status', 'created_at']);
            $table->index(['consumer_id', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('line_total');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
        Schema::dropIfExists('stores');
    }
};
