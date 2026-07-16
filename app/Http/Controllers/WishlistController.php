<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $wishlist = Wishlist::with('product.images')
            ->where('user_id', $request->user()->id)
            ->get();

        return response()->json(['success' => true, 'data' => $wishlist]);
    }

    public function store(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);

        // Mencegah duplikat
        $exists = Wishlist::where('user_id', $request->user()->id)
                          ->where('product_id', $request->product_id)->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Produk sudah ada di wishlist'], 400);
        }

        Wishlist::create([
            'user_id' => $request->user()->id,
            'product_id' => $request->product_id
        ]);

        return response()->json(['success' => true, 'message' => 'Berhasil ditambah ke wishlist']);
    }

    public function destroy($id)
{
    // Coba hapus berdasarkan ID wishlist ATAU ID product
    $deleted = Wishlist::where('user_id', auth()->id())
                       ->where(function($query) use ($id) {
                           $query->where('id', $id)
                                 ->orWhere('product_id', $id);
                       })->delete();

    return response()->json(['success' => true, 'message' => 'Berhasil dihapus']);
}
}