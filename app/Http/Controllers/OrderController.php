<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->orders()->with('store')->latest()->paginate(12);

        return view('consumer.orders', compact('orders'));
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($order->consumer_id === $request->user()->id, 404);
        $order->load(['store', 'items']);

        return view('consumer.order', compact('order'));
    }

    public function confirmDistance(Request $request, Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        abort_unless($order->consumer_id === $request->user()->id, 404);

        DB::transaction(function () use ($order, $workflow): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->order_status === 'awaiting_customer_confirmation', 422);

            $workflow->reserveStock($locked->load('items'));
            $locked->update(['order_status' => 'pending']);
        });

        return back()->with('status', 'Total disetujui. Pesanan kini menunggu konfirmasi toko.');
    }

    public function cancel(Request $request, Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        abort_unless($order->consumer_id === $request->user()->id, 404);
        $data = $request->validate(['cancellation_reason' => ['required', 'string', 'max:2000']]);

        DB::transaction(function () use ($order, $data, $workflow): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->order_status, ['awaiting_distance', 'awaiting_customer_confirmation', 'pending'], true)) {
                $workflow->releaseStock($locked->load('items'));
                $locked->update([
                    'order_status' => 'cancelled',
                    'cancellation_reason' => $data['cancellation_reason'],
                    'cancellation_decided_at' => now(),
                    'cancellation_decision' => 'approved',
                    'payment_status' => $locked->payment_status === 'paid' ? 'refund_pending' : $locked->payment_status,
                ]);

                return;
            }

            abort_unless($locked->order_status === 'accepted', 422, 'Pesanan ini tidak dapat dibatalkan melalui aplikasi.');
            $locked->update([
                'cancellation_previous_status' => $locked->order_status,
                'cancellation_reason' => $data['cancellation_reason'],
                'cancellation_requested_at' => now(),
                'order_status' => 'cancellation_requested',
            ]);
        });

        return back()->with('status', 'Permintaan pembatalan telah dicatat.');
    }

    public function uploadProof(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->consumer_id === $request->user()->id, 404);
        abort_unless($order->payment_method === 'transfer', 422, 'Pesanan ini tidak menggunakan transfer.');
        abort_unless(in_array($order->order_status, ['pending', 'accepted', 'preparing', 'ready_for_pickup', 'on_delivery'], true), 422);
        abort_unless(in_array($order->payment_status, ['unpaid', 'rejected'], true), 422, 'Bukti pembayaran sedang diverifikasi atau pembayaran sudah tercatat.');

        $data = $request->validate([
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $path = $data['payment_proof']->store('payment-proofs', 'local');
        if (! is_string($path)) {
            throw new \RuntimeException('Bukti pembayaran gagal disimpan.');
        }

        if ($order->payment_proof_path) {
            Storage::disk('local')->delete($order->payment_proof_path);
        }

        $order->update([
            'payment_proof_path' => $path,
            'payment_status' => 'awaiting_verification',
            'payment_rejection_reason' => null,
            'payment_verified_at' => null,
        ]);

        return back()->with('status', 'Bukti transfer berhasil dikirim untuk diverifikasi.');
    }

    public function downloadProof(Order $order): StreamedResponse
    {
        abort_unless($order->store?->user_id === request()->user()->id, 404);
        abort_unless($order->payment_proof_path && Storage::disk('local')->exists($order->payment_proof_path), 404);

        return Storage::disk('local')->download($order->payment_proof_path);
    }
}
