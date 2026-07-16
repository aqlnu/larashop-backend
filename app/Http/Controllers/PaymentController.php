<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    // 1. USER - Upload Bukti Pembayaran
    public function uploadProof(Request $request, $orderId)
    {
        $validator = Validator::make($request->all(), [
            'proof_of_payment' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'data' => $validator->errors()], 422);
        }

        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Pesanan tidak ditemukan', 'data' => null], 404);
        }

        // Pastikan pesanan milik user yang sedang login
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak', 'data' => null], 403);
        }

        $payment = Payment::where('order_id', $orderId)->first();
        
        // Upload gambar ke storage
        $path = $request->file('proof_of_payment')->store('payments', 'public');

        $payment->update([
            'proof_of_payment' => $path
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi admin.',
            'data' => $payment
        ], 200);
    }

    // 2. ADMIN - Verifikasi Pembayaran
    public function verify(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak', 'data' => null], 403);
        }

        $payment = Payment::with('order')->find($id);

        // Fallback: frontend admin mengirim ID Order, bukan ID Payment
        if (!$payment) {
            $payment = Payment::with('order')->where('order_id', $id)->first();
        }

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Data pembayaran tidak ditemukan', 'data' => null], 404);
        }

        // Ubah status pembayaran jadi verified (sesuai API contract)
        $payment->update(['status' => 'verified']);

        // Otomatis ubah status order menjadi Processing
        $payment->order->update(['status' => 'Processing']);

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil diverifikasi. Status pesanan menjadi Processing.',
            'data' => $payment
        ], 200);
    }

    // 3. ADMIN - Tolak Pembayaran
    public function reject(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak', 'data' => null], 403);
        }

        $payment = Payment::find($id);

        if (!$payment) {
            $payment = Payment::where('order_id', $id)->first();
        }

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Data pembayaran tidak ditemukan', 'data' => null], 404);
        }

        $payment->update(['status' => 'rejected']);

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran ditolak',
            'data' => $payment
        ], 200);
    }
}