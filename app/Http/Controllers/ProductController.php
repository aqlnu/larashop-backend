<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // Helper: terapkan filter query yang dipakai halaman produk & pencarian frontend
    private function applyFilters($query, Request $request)
    {
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->boolean('in_stock')) {
            $query->where('stock', '>', 0);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        switch ($request->input('sort')) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'best_selling':
                $query->withSum('orderItems as sold_count', 'quantity')
                      ->orderByDesc('sold_count');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        if ($request->filled('limit')) {
            $query->limit((int) $request->limit);
        }

        return $query;
    }

    // 1. READ ALL (Publik) - Menampilkan semua produk aktif, mendukung filter & sort
    public function index(Request $request)
    {
        $query = Product::with(['category', 'images'])->where('is_active', true);
        $query = $this->applyFilters($query, $request);
        $products = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar semua produk berhasil diambil',
            'data' => $products
        ], 200);
    }

    // 1b. SEARCH (Publik) - Alias dari index dengan filter, dipakai frontend saat search diisi
    public function search(Request $request)
    {
        return $this->index($request);
    }

    // 1c. READ ALL (Admin) - Menampilkan semua produk termasuk yang nonaktif
    public function indexAdmin(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak', 'data' => null], 403);
        }

        $products = Product::with(['category', 'images'])->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar semua produk (admin) berhasil diambil',
            'data' => $products
        ], 200);
    }

    // 2. CREATE (Admin) - Menambah produk baru
    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Admin yang dapat menambah produk.',
                'data' => null
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'sku' => 'nullable|string|unique:products,sku',
            'weight' => 'nullable|integer|min:0',
            'material' => 'nullable|string|max:255',
            'dimension' => 'nullable|string|max:255',
            'images' => 'nullable|array', // Menerima array file gambar jika langsung diupload
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'data' => $validator->errors()
            ], 422);
        }

        // Simpan data produk
        $product = Product::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . Str::random(5),
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
            'sku' => $request->sku,
            'weight' => $request->input('weight', 0),
            'material' => $request->material,
            'dimension' => $request->dimension,
            'is_active' => $request->input('is_active', true),
        ]);

        // Proses upload gambar jika ada file yang dikirim langsung
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $imageFile) {
                $path = $imageFile->store('products', 'public');

                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'is_primary' => $index === 0 // Gambar pertama otomatis jadi gambar utama
                ]);
            }
        }

        // Muat ulang produk beserta relasinya untuk response
        $product->load(['category', 'images']);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan',
            'data' => $product
        ], 201);
    }

    // 3. READ DETAIL (Publik) - Menampilkan detail satu produk
    public function show($id)
    {
        $product = Product::with(['category', 'images'])->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail produk berhasil diambil',
            'data' => $product
        ], 200);
    }

    // 4. UPDATE (Admin) - Mengubah data produk
    public function update(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.', 'data' => null], 403);
        }

        $product = Product::find($id);

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan', 'data' => null], 404);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'sku' => 'nullable|string|unique:products,sku,' . $product->id,
            'weight' => 'nullable|integer|min:0',
            'material' => 'nullable|string|max:255',
            'dimension' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'data' => $validator->errors()], 422);
        }

        $product->update([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . $product->id,
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
            'sku' => $request->sku,
            'weight' => $request->input('weight', $product->weight),
            'material' => $request->material,
            'dimension' => $request->dimension,
            'is_active' => $request->input('is_active', $product->is_active),
        ]);

        $product->load(['category', 'images']);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui',
            'data' => $product
        ], 200);
    }

    // 4b. UPLOAD IMAGES (Admin) - Upload gambar tambahan setelah produk dibuat/diedit
    public function uploadImages(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.', 'data' => null], 403);
        }

        $product = Product::find($id);

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan', 'data' => null], 404);
        }

        $validator = Validator::make($request->all(), [
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'data' => $validator->errors()], 422);
        }

        $hasPrimary = $product->images()->where('is_primary', true)->exists();

        foreach ($request->file('images') as $index => $imageFile) {
            $path = $imageFile->store('products', 'public');

            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'is_primary' => !$hasPrimary && $index === 0,
            ]);
        }

        $product->load(['category', 'images']);

        return response()->json([
            'success' => true,
            'message' => 'Gambar produk berhasil diunggah',
            'data' => $product
        ], 200);
    }

    // 5. DELETE (Admin) - Menghapus produk
    public function destroy(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.', 'data' => null], 403);
        }

        $product = Product::find($id);

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan', 'data' => null], 404);
        }

        // Hapus file gambar fisik dari storage sebelum menghapus data di database
        $images = ProductImage::where('product_id', $product->id)->get();
        foreach ($images as $img) {
            Storage::disk('public')->delete($img->image_path);
        }

        $product->delete(); // Karena onDelete('cascade'), baris di product_images otomatis ikut terhapus

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus',
            'data' => null
        ], 200);
    }
}
