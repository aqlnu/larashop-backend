<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    // Rekap penjualan bulanan (12 bulan terakhir)
    public function sales(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak', 'data' => null], 403);
        }

        $paidStatuses = ['Paid', 'Processing', 'Shipped', 'Completed'];

        $orders = Order::whereIn('status', $paidStatuses)->get();

        $totalOrders = $orders->count();
        $totalRevenue = (float) $orders->sum('total_price');
        $avgOrder = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        $totalItemsSold = (int) OrderItem::whereIn(
            'order_id',
            $orders->pluck('id')
        )->sum('quantity');

        // Chart per bulan, 12 bulan terakhir
        $chart = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthOrders = $orders->filter(function ($order) use ($month) {
                return Carbon::parse($order->created_at)->format('Y-m') === $month->format('Y-m');
            });

            $chart[] = [
                'label' => $month->translatedFormat('M Y'),
                'orders' => $monthOrders->count(),
                'value' => (float) $monthOrders->sum('total_price'),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Laporan penjualan berhasil diambil',
            'data' => [
                'summary' => [
                    'total_orders' => $totalOrders,
                    'total_revenue' => $totalRevenue,
                    'avg_order' => $avgOrder,
                    'total_items_sold' => $totalItemsSold,
                ],
                'chart' => $chart,
            ]
        ], 200);
    }

    // Produk terlaris
    public function products(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak', 'data' => null], 403);
        }

        $paidStatuses = ['Paid', 'Processing', 'Shipped', 'Completed'];
        $orderIds = Order::whereIn('status', $paidStatuses)->pluck('id');

        $topProducts = OrderItem::with('product.images')
            ->whereIn('order_id', $orderIds)
            ->get()
            ->groupBy('product_id')
            ->map(function ($items) {
                $product = $items->first()->product;
                $sold = $items->sum('quantity');
                $revenue = $items->sum(function ($item) {
                    return $item->price * $item->quantity;
                });

                return [
                    'id' => $product?->id,
                    'name' => $product?->name ?? 'Produk dihapus',
                    'image' => $product?->images?->first()?->url,
                    'sold' => (int) $sold,
                    'revenue' => (float) $revenue,
                ];
            })
            ->sortByDesc('sold')
            ->values()
            ->take(10);

        return response()->json([
            'success' => true,
            'message' => 'Laporan produk terlaris berhasil diambil',
            'data' => $topProducts
        ], 200);
    }
}
