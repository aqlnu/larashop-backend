<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    // 1. READ (User) - Melihat isi keranjang milik user yang sedang login
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Cari keranjang user, ambil beserta item di dalamnya dan data produknya
        $cart = Cart::with(['items.product.images'])->firstOrCreate(
            ['user_id' => $user->id]
        );

        return response()->json([
            'success' => true,
            'message' => 'Data keranjang berhasil diambil',
            'data' => $cart
        ], 200);
    }

    // 2. CREATE / UPDATE (User) - Memasukkan barang ke keranjang
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'data' => $validator->errors()], 422);
        }

        $user = $request->user();
        $product = Product::find($request->product_id);

        // Cek apakah stok cukup
        if ($product->stock < $request->quantity) {
            return response()->json(['success' => false, 'message' => 'Stok produk tidak mencukupi', 'data' => null], 400);
        }

        // Cari atau buat keranjang untuk user ini
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        // Cek apakah barang sudah ada di keranjang
        $existingItem = CartItem::where('cart_id', $cart->id)
                                ->where('product_id', $request->product_id)
                                ->first();

        if ($existingItem) {
            // Jika ada, tambahkan quantity-nya
            $newQuantity = $existingItem->quantity + $request->quantity;
            
            if ($product->stock < $newQuantity) {
                return response()->json(['success' => false, 'message' => 'Total kuantitas melebihi stok yang ada', 'data' => null], 400);
            }

            $existingItem->update(['quantity' => $newQuantity]);
        } else {
            // Jika belum ada, buat item baru di keranjang
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity
            ]);
        }

        // Ambil data terbaru untuk di-return
        $cart->load('items.product.images');

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan ke keranjang',
            'data' => $cart
        ], 200);
    }

    // 3. UPDATE DETAIL (User) - Mengubah jumlah barang tertentu di keranjang (+ / -)
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'data' => $validator->errors()], 422);
        }

        $cartItem = CartItem::find($id);

        if (!$cartItem) {
            return response()->json(['success' => false, 'message' => 'Item tidak ditemukan di keranjang', 'data' => null], 404);
        }

        // Pastikan keranjang ini milik user yang sedang login
        if ($cartItem->cart->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak', 'data' => null], 403);
        }

        // Cek stok produk
        if ($cartItem->product->stock < $request->quantity) {
            return response()->json(['success' => false, 'message' => 'Stok produk tidak mencukupi', 'data' => null], 400);
        }

        $cartItem->update(['quantity' => $request->quantity]);

        return response()->json([
            'success' => true,
            'message' => 'Jumlah barang berhasil diupdate',
            'data' => $cartItem
        ], 200);
    }

    // 4. DELETE (User) - Menghapus 1 jenis barang dari keranjang
    public function destroy(Request $request, $id)
    {
        $cartItem = CartItem::find($id);

        if (!$cartItem) {
            return response()->json(['success' => false, 'message' => 'Item tidak ditemukan', 'data' => null], 404);
        }

        if ($cartItem->cart->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak', 'data' => null], 403);
        }

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Barang berhasil dihapus dari keranjang',
            'data' => null
        ], 200);
    }
}