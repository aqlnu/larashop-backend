<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BannerController extends Controller
{
    // 1. READ ALL (Admin) - Menampilkan semua banner
    public function index(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak', 'data' => null], 403);
        }

        $banners = Banner::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar banner berhasil diambil',
            'data' => $banners
        ], 200);
    }

    // 2. CREATE (Admin) - Menambah banner baru (tanpa gambar dulu, gambar diupload terpisah)
    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak', 'data' => null], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'link' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'data' => $validator->errors()], 422);
        }

        $banner = Banner::create([
            'title' => $request->title,
            'link' => $request->link,
            'is_active' => $request->input('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Banner berhasil ditambahkan',
            'data' => $banner
        ], 201);
    }

    // 3. UPDATE (Admin)
    public function update(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak', 'data' => null], 403);
        }

        $banner = Banner::find($id);

        if (!$banner) {
            return response()->json(['success' => false, 'message' => 'Banner tidak ditemukan', 'data' => null], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'link' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'data' => $validator->errors()], 422);
        }

        $banner->update([
            'title' => $request->title,
            'link' => $request->link,
            'is_active' => $request->input('is_active', $banner->is_active),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Banner berhasil diperbarui',
            'data' => $banner
        ], 200);
    }

    // 4. UPLOAD IMAGE (Admin) - Upload/replace gambar banner setelah banner dibuat
    public function uploadImage(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak', 'data' => null], 403);
        }

        $banner = Banner::find($id);

        if (!$banner) {
            return response()->json(['success' => false, 'message' => 'Banner tidak ditemukan', 'data' => null], 404);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'data' => $validator->errors()], 422);
        }

        // Hapus gambar lama jika ada
        if ($banner->image_path) {
            Storage::disk('public')->delete($banner->image_path);
        }

        $path = $request->file('image')->store('banners', 'public');
        $banner->update(['image_path' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Gambar banner berhasil diunggah',
            'data' => $banner
        ], 200);
    }

    // 5. DELETE (Admin)
    public function destroy(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak', 'data' => null], 403);
        }

        $banner = Banner::find($id);

        if (!$banner) {
            return response()->json(['success' => false, 'message' => 'Banner tidak ditemukan', 'data' => null], 404);
        }

        if ($banner->image_path) {
            Storage::disk('public')->delete($banner->image_path);
        }

        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Banner berhasil dihapus',
            'data' => null
        ], 200);
    }
}
