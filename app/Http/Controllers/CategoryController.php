<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    // 1. READ ALL (Publik) - Menampilkan semua kategori beserta jumlah produknya
    public function index()
    {
        $categories = Category::withCount('products')->get();

        // Frontend membaca field "product_count" (bukan "products_count" default Laravel)
        $categories->each(function ($category) {
            $category->product_count = $category->products_count;
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar semua kategori berhasil diambil',
            'data' => $categories
        ], 200);
    }

    // 2. CREATE (Admin) - Menambah kategori baru
    public function store(Request $request)
    {
        // Pengecekan sederhana apakah user yang login adalah admin
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Admin yang dapat menambah kategori.',
                'data' => null
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'data' => $validator->errors()
            ], 422);
        }

        $category = Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name), // Otomatis membuat slug dari nama (misal: "Meja Kayu" -> "meja-kayu")
            'description' => $request->description,
            'icon' => $request->icon
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil ditambahkan',
            'data' => $category
        ], 201);
    }

    // 3. READ DETAIL (Publik) - Menampilkan 1 kategori spesifik
    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak ditemukan',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail kategori berhasil diambil',
            'data' => $category
        ], 200);
    }

    // 4. UPDATE (Admin) - Mengubah data kategori
    public function update(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.', 'data' => null], 403);
        }

        $category = Category::find($id);

        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Kategori tidak ditemukan', 'data' => null], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'data' => $validator->errors()], 422);
        }

        $category->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'icon' => $request->icon
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil diperbarui',
            'data' => $category
        ], 200);
    }

    // 5. DELETE (Admin) - Menghapus kategori
    public function destroy(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.', 'data' => null], 403);
        }

        $category = Category::find($id);

        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Kategori tidak ditemukan', 'data' => null], 404);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dihapus',
            'data' => null
        ], 200);
    }
}