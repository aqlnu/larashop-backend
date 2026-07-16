<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    // 1. READ ALL (User) - Melihat riwayat pesanan user yang sedang login
    public function index(Request $request)
    {
        $orders = Order::with(['items.product', 'payment'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pesanan berhasil diambil',
            'data' => $orders
        ], 200);
    }

    // 2. CREATE (User) - Melakukan Checkout dari Keranjang
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shipping_address' => 'required|string',
            'payment_method' => 'required|string', // misal: "Transfer Bank"
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'data' => $validator->errors()], 422);
        }

        $user = $request->user();
        
        // Ambil keranjang user beserta itemnya
        $cart = Cart::with('items.product')->where('user_id', $user->id)->first();

        if (!$cart || $cart->items->count() === 0) {
            return response()->json(['success' => false, 'message' => 'Keranjang belanja kosong', 'data' => null], 400);
        }

        // Mulai Database Transaction untuk keamanan data
        DB::beginTransaction();

        try {
            $totalPrice = 0;

            // Cek stok sekali lagi dan hitung total harga
            foreach ($cart->items as $item) {
                if ($item->product->stock < $item->quantity) {
                    throw new \Exception('Stok tidak mencukupi untuk produk: ' . $item->product->name);
                }
                $totalPrice += ($item->product->price * $item->quantity);
            }

            // Buat nomor order unik (Format: ORD-YYYYMMDD-RandomID)
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());

            // 1. Buat data Order
            // Status wajib "Pending Payment" sesuai API Contract
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $orderNumber,
                'total_price' => $totalPrice,
                'status' => 'Pending Payment', 
                'shipping_address' => $request->shipping_address,
                'notes' => $request->notes
            ]);

            // 2. Pindahkan data dari Cart Item ke Order Item & Potong Stok
            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price // Simpan harga saat ini (menghindari perubahan harga di masa depan)
                ]);

                // Potong stok produk
                $product = Product::find($item->product_id);
                $product->decrement('stock', $item->quantity);
            }

            // 3. Buat data tagihan Payment
            // Status wajib "pending" (huruf kecil) sesuai API Contract
            Payment::create([
                'order_id' => $order->id,
                'payment_method' => $request->payment_method,
                'amount' => $totalPrice,
                'status' => 'pending'
            ]);

            // 4. Kosongkan keranjang user
            $cart->items()->delete();

            DB::commit(); // Simpan semua perubahan ke database

            // Load data lengkap untuk di-return
            $order->load(['items.product', 'payment']);

            return response()->json([
                'success' => true,
                'message' => 'Checkout berhasil, pesanan dibuat',
                'data' => $order
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan semua jika ada error (stok tidak jadi terpotong)
            return response()->json([
                'success' => false,
                'message' => 'Checkout gagal: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    // 3. READ DETAIL (User) - Melihat detail satu pesanan
    public function show(Request $request, $id)
    {
        $order = Order::with(['items.product', 'payment'])->find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Pesanan tidak ditemukan', 'data' => null], 404);
        }

        // Pastikan hanya pemilik pesanan (atau admin) yang bisa melihat
        if ($order->user_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak', 'data' => null], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail pesanan berhasil diambil',
            'data' => $order
        ], 200);
    }
    // 4. ADMIN - Melihat SEMUA pesanan dari semua user
    public function indexAdmin(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak', 'data' => null], 403);
        }

        $orders = Order::with(['user', 'items.product', 'payment'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Semua data pesanan berhasil diambil',
            'data' => $orders
        ], 200);
    }

    // 5. ADMIN - Update Status Pesanan (Pengiriman)
    public function updateStatus(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak', 'data' => null], 403);
        }

        $validator = Validator::make($request->all(), [
            // Status wajib persis seperti di API Contract
            'status' => 'required|string|in:Pending Payment,Paid,Processing,Shipped,Completed,Cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'data' => $validator->errors()], 422);
        }

        $order = Order::find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Pesanan tidak ditemukan', 'data' => null], 404);
        }

        $order->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Status pesanan berhasil diperbarui',
            'data' => $order
        ], 200);
    }
    // 6. CANCEL (User) - Membatalkan pesanan
    public function cancel(Request $request, $id)
    {
        // Ambil data order beserta isinya
        $order = Order::with('items')->find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Pesanan tidak ditemukan', 'data' => null], 404);
        }

        // Pastikan pesanan ini benar-benar milik user yang sedang login
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak', 'data' => null], 403);
        }

        // Cegah pembatalan jika pesanan sudah dibayar atau diproses admin
        if ($order->status !== 'Pending Payment') {
            return response()->json(['success' => false, 'message' => 'Pesanan tidak dapat dibatalkan karena status sudah ' . $order->status, 'data' => null], 400);
        }

        // Mulai transaksi database untuk mengamankan data
        DB::beginTransaction();

        try {
            // 1. Ubah status order menjadi Cancelled
            $order->update(['status' => 'Cancelled']);

            // 2. Kembalikan stok produk yang tadi sempat terpotong saat checkout
            foreach ($order->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->increment('stock', $item->quantity);
                }
            }

            // 3. (Opsional) Ubah status di tabel Payment menjadi failed
            $payment = Payment::where('order_id', $order->id)->first();
            if ($payment) {
                $payment->update(['status' => 'failed']);
            }

            DB::commit(); // Simpan perubahan

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibatalkan dan stok telah dikembalikan',
                'data' => $order
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan semua jika ada error
            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan pesanan: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}