<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AntarasaFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_offers_only_the_two_application_roles_and_starts_without_sample_accounts(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Untuk Konsumen')
            ->assertSee('Untuk Mitra')
            ->assertSee('/masuk/konsumen', false)
            ->assertSee('/masuk/mitra', false)
            ->assertDontSee('Owner');

        $this->assertSame(0, User::count());
    }

    public function test_guests_are_sent_to_the_login_for_the_requested_application_area(): void
    {
        $this->get(route('consumer.home'))->assertRedirect(route('login', 'konsumen'));
        $this->get(route('partner.dashboard'))->assertRedirect(route('login', 'mitra'));
    }

    public function test_mitra_self_registration_stays_pending_and_cannot_sign_in_before_activation(): void
    {
        $this->post(route('register.store', 'mitra'), [
            'name' => 'Pemilik Usaha',
            'email' => 'mitra@example.test',
            'username' => 'mitra-lokal',
            'phone' => '081234567890',
            'store_name' => 'Dapur Sejuk',
            'address' => 'Jalan Melati 10',
            'password' => 'password-kuat',
            'password_confirmation' => 'password-kuat',
        ])->assertRedirect(route('login', 'mitra'));

        $user = User::where('email', 'mitra@example.test')->firstOrFail();
        $this->assertSame('pending', $user->account_status);
        $this->assertSame('pending', $user->store->store_status);

        $this->post(route('login.store', 'mitra'), [
            'identity' => 'mitra@example.test',
            'password' => 'password-kuat',
        ])->assertSessionHasErrors('identity');
    }

    public function test_delivery_order_uses_verified_tariff_and_reserves_stock_only_after_customer_approval(): void
    {
        [$partner, $store, $product, $consumer] = $this->createShop();

        $this->actingAs($consumer)->withSession([
            'cart' => ['store_id' => $store->id, 'items' => [$product->id => 2]],
            'checkout_token' => str_repeat('a', 64),
        ])->post(route('checkout.store'), [
            'checkout_token' => str_repeat('a', 64),
            'delivery_method' => 'delivery',
            'payment_method' => 'cod',
            'recipient_name' => 'Pelanggan',
            'recipient_phone' => '081111111111',
            'delivery_address' => 'Jalan Anggrek 2',
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('awaiting_distance', $order->order_status);
        $this->assertSame(0, $order->delivery_fee);
        $this->assertSame(10, $product->fresh()->stock_qty);
        $this->assertSame('Nasi hangat', $order->items->first()->product_name);

        $this->actingAs($partner)->post(route('partner.orders.distance', $order), [
            'distance_km' => '2.01',
        ])->assertRedirect();

        $order->refresh();
        $this->assertSame('awaiting_customer_confirmation', $order->order_status);
        $this->assertSame(8000, $order->delivery_fee);
        $this->assertSame(28000, $order->total);
        $this->assertSame(10, $product->fresh()->stock_qty);

        $this->actingAs($consumer)->post(route('orders.confirm-distance', $order))
            ->assertRedirect();

        $this->assertSame('pending', $order->fresh()->order_status);
        $this->assertTrue($order->fresh()->stock_reserved);
        $this->assertSame(8, $product->fresh()->stock_qty);
    }

    public function test_delivery_beyond_five_kilometers_is_rejected_without_reserving_stock(): void
    {
        [$partner, $store, $product, $consumer] = $this->createShop();

        $this->actingAs($consumer)->withSession([
            'cart' => ['store_id' => $store->id, 'items' => [$product->id => 1]],
            'checkout_token' => str_repeat('b', 64),
        ])->post(route('checkout.store'), [
            'checkout_token' => str_repeat('b', 64),
            'delivery_method' => 'delivery',
            'payment_method' => 'cod',
            'recipient_name' => 'Pelanggan',
            'recipient_phone' => '081111111111',
            'delivery_address' => 'Alamat tujuan',
        ]);
        $order = Order::firstOrFail();

        $this->actingAs($partner)->post(route('partner.orders.distance', $order), ['distance_km' => '5.01'])
            ->assertRedirect();

        $this->assertSame('rejected', $order->fresh()->order_status);
        $this->assertSame(10, $product->fresh()->stock_qty);
        $this->assertFalse($order->fresh()->stock_reserved);
    }

    public function test_rejected_pickup_order_returns_reserved_stock_once(): void
    {
        [$partner, $store, $product, $consumer] = $this->createShop();

        $this->actingAs($consumer)->withSession([
            'cart' => ['store_id' => $store->id, 'items' => [$product->id => 3]],
            'checkout_token' => str_repeat('c', 64),
        ])->post(route('checkout.store'), [
            'checkout_token' => str_repeat('c', 64),
            'delivery_method' => 'pickup',
            'payment_method' => 'cod',
        ]);

        $order = Order::firstOrFail();
        $this->assertSame(7, $product->fresh()->stock_qty);
        $this->assertTrue($order->stock_reserved);

        $this->post(route('checkout.store'), [
            'checkout_token' => str_repeat('c', 64),
            'delivery_method' => 'pickup',
            'payment_method' => 'cod',
        ])->assertRedirect(route('orders.show', $order));
        $this->assertSame(1, Order::count());
        $this->assertSame(7, $product->fresh()->stock_qty);

        $this->actingAs($partner)->post(route('partner.orders.decision', $order), ['decision' => 'reject'])
            ->assertRedirect();
        $this->assertSame(10, $product->fresh()->stock_qty);
        $this->assertFalse($order->fresh()->stock_reserved);

        $this->post(route('partner.orders.decision', $order), ['decision' => 'reject'])
            ->assertStatus(422);
        $this->assertSame(10, $product->fresh()->stock_qty);
    }

    public function test_consumer_can_upload_private_transfer_proof_for_partner_verification(): void
    {
        Storage::fake('local');
        [$partner, $store, $product, $consumer] = $this->createShop();
        $order = Order::create([
            'order_code' => 'ARTEST000003',
            'consumer_id' => $consumer->id,
            'store_id' => $store->id,
            'order_status' => 'pending',
            'delivery_method' => 'pickup',
            'payment_method' => 'transfer',
            'payment_status' => 'unpaid',
            'subtotal' => 10000,
            'delivery_fee' => 0,
            'total' => 10000,
        ]);

        $this->actingAs($consumer)->post(route('orders.payment-proof', $order), [
            'payment_proof' => UploadedFile::fake()->image('transfer.png'),
        ])->assertRedirect();

        $order->refresh();
        $this->assertSame('awaiting_verification', $order->payment_status);
        $this->assertNotNull($order->payment_proof_path);
        Storage::disk('local')->assertExists($order->payment_proof_path);

        $this->actingAs($partner)->get(route('partner.orders.payment-proof', $order))->assertOk();
        $this->post(route('partner.orders.payment', $order), [
            'decision' => 'rejected',
            'payment_rejection_reason' => 'Nominal belum dapat diverifikasi.',
        ])->assertRedirect();
        $this->assertSame('rejected', $order->fresh()->payment_status);
    }

    public function test_a_mitra_cannot_view_another_mitras_order(): void
    {
        [$partner, $store, $product, $consumer] = $this->createShop();
        $order = Order::create([
            'order_code' => 'ARTEST000001',
            'consumer_id' => $consumer->id,
            'store_id' => $store->id,
            'order_status' => 'pending',
            'delivery_method' => 'pickup',
            'payment_method' => 'cod',
            'subtotal' => 100,
            'delivery_fee' => 0,
            'total' => 100,
        ]);
        $otherPartner = User::create([
            'name' => 'Mitra lain',
            'email' => 'lain@example.test',
            'username' => 'mitra-lain',
            'phone' => '082222222222',
            'password' => 'password-kuat',
            'role' => 'mitra',
            'account_status' => 'active',
        ]);
        Store::create([
            'user_id' => $otherPartner->id,
            'store_name' => 'Toko lain',
            'store_status' => 'active',
        ]);

        $this->actingAs($otherPartner)->get(route('partner.orders.show', $order))->assertNotFound();
    }

    public function test_consumer_and_partner_pages_render_with_persisted_records(): void
    {
        [$partner, $store, $product, $consumer] = $this->createShop();
        $order = Order::create([
            'order_code' => 'ARTEST000002',
            'consumer_id' => $consumer->id,
            'store_id' => $store->id,
            'order_status' => 'pending',
            'delivery_method' => 'pickup',
            'payment_method' => 'cod',
            'subtotal' => 10000,
            'delivery_fee' => 0,
            'total' => 10000,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => $product->price,
            'quantity' => 1,
            'line_total' => $product->price,
        ]);

        $this->actingAs($consumer)->get(route('consumer.home'))->assertOk();
        $this->get(route('consumer.store', $store))->assertOk();
        $this->withSession(['cart' => ['store_id' => $store->id, 'items' => [$product->id => 1]]])
            ->get(route('checkout'))->assertOk();
        $this->get(route('orders.index'))->assertOk();
        $this->get(route('orders.show', $order))->assertOk();

        $this->actingAs($partner)->get(route('partner.dashboard'))->assertOk();
        $this->get(route('partner.store.edit'))->assertOk();
        $this->get(route('partner.products'))->assertOk();
        $this->get(route('partner.products.create'))->assertOk();
        $this->get(route('partner.orders'))->assertOk();
        $this->get(route('partner.orders.show', $order))->assertOk();
    }

    public function test_delivery_order_can_be_verified_prepared_dispatched_and_completed(): void
    {
        [$partner, $store, $product, $consumer] = $this->createShop();

        $this->actingAs($consumer)->withSession([
            'cart' => ['store_id' => $store->id, 'items' => [$product->id => 1]],
            'checkout_token' => str_repeat('d', 64),
        ])->post(route('checkout.store'), [
            'checkout_token' => str_repeat('d', 64),
            'delivery_method' => 'delivery',
            'payment_method' => 'cod',
            'recipient_name' => 'Pelanggan',
            'recipient_phone' => '081111111111',
            'delivery_address' => 'Jalan Anggrek 2',
        ]);
        $order = Order::firstOrFail();

        $this->actingAs($partner)->get(route('partner.orders.show', $order))
            ->assertOk()
            ->assertSee('Verifikasi rute manual');
        $this->post(route('partner.orders.distance', $order), ['distance_km' => '3.50']);
        $this->actingAs($consumer)->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Setujui dan aktifkan pesanan');
        $this->post(route('orders.confirm-distance', $order));

        $this->actingAs($partner)->post(route('partner.orders.decision', $order), ['decision' => 'accept']);
        $this->get(route('partner.orders.show', $order))->assertOk()->assertSee('Informasi driver');
        $this->put(route('partner.orders.driver', $order), [
            'driver_booking_status' => 'booked',
            'driver_name' => 'Driver Antar',
            'driver_phone' => '081234567899',
            'driver_cost' => 12000,
        ]);
        $this->post(route('partner.orders.status', $order), ['order_status' => 'preparing']);
        $this->post(route('partner.orders.status', $order), ['order_status' => 'ready_for_pickup']);
        $this->post(route('partner.orders.status', $order), ['order_status' => 'on_delivery']);
        $this->post(route('partner.orders.status', $order), ['order_status' => 'completed']);

        $this->assertSame('completed', $order->fresh()->order_status);
        $this->assertSame(12000, $order->fresh()->driver_cost);
        $this->assertSame(8000, $order->fresh()->delivery_fee);
        $this->actingAs($consumer)->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Driver Antar')
            ->assertSee('081234567899');
    }

    private function createShop(): array
    {
        $partner = User::create([
            'name' => 'Mitra Antarasa',
            'email' => 'mitra-aktif@example.test',
            'username' => 'mitra-aktif',
            'phone' => '081234567891',
            'password' => 'password-kuat',
            'role' => 'mitra',
            'account_status' => 'active',
        ]);
        $store = Store::create([
            'user_id' => $partner->id,
            'store_name' => 'Dapur Lokal',
            'store_status' => 'active',
            'is_open' => true,
        ]);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Nasi hangat',
            'price' => 10000,
            'stock_qty' => 10,
            'availability_status' => 'available',
        ]);
        $consumer = User::create([
            'name' => 'Konsumen',
            'email' => 'konsumen@example.test',
            'username' => 'konsumen',
            'phone' => '081111111111',
            'password' => 'password-kuat',
            'role' => 'konsumen',
            'account_status' => 'active',
        ]);

        return [$partner, $store, $product, $consumer];
    }
}
