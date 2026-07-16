<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak', 'data' => null], 403);
        }

        $paidStatuses = ['Paid', 'Processing', 'Shipped', 'Completed'];

        $totalSales = Order::whereIn('status', $paidStatuses)->count();
        $totalRevenue = Order::whereIn('status', $paidStatuses)->sum('total_price');
        $totalProducts = Product::count();
        $totalCustomers = User::where('role', 'user')->count();

        // Grafik penjualan 7 hari terakhir
        $salesChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $value = Order::whereIn('status', $paidStatuses)
                ->whereDate('created_at', $date->toDateString())
                ->sum('total_price');

            $salesChart[] = [
                'label' => $date->translatedFormat('d M'),
                'value' => (float) $value,
            ];
        }

        $recentOrders = Order::with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data dashboard berhasil diambil',
            'data' => [
                'metrics' => [
                    'total_sales' => $totalSales,
                    'total_revenue' => (float) $totalRevenue,
                    'total_products' => $totalProducts,
                    'total_customers' => $totalCustomers,
                ],
                'sales_chart' => $salesChart,
                'recent_orders' => $recentOrders,
            ]
        ], 200);
    }
}
