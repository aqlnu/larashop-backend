<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    // 1. READ ALL (Admin) - Menampilkan semua pelanggan (role = user) beserta ringkasan transaksinya
    public function index(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak', 'data' => null], 403);
        }

        $customers = User::where('role', 'user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                $orders = Order::where('user_id', $user->id);
                $user->total_orders = (clone $orders)->count();
                $user->total_spent = (clone $orders)
                    ->whereNotIn('status', ['Pending Payment', 'Cancelled'])
                    ->sum('total_price');
                return $user;
            });

        return response()->json([
            'success' => true,
            'message' => 'Daftar pelanggan berhasil diambil',
            'data' => $customers
        ], 200);
    }

    // 2. DELETE (Admin) - Menghapus akun pelanggan
    public function destroy(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak', 'data' => null], 403);
        }

        $user = User::where('role', 'user')->find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Pelanggan tidak ditemukan', 'data' => null], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pelanggan berhasil dihapus',
            'data' => null
        ], 200);
    }
}
