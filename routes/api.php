<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;

// ==========================================
// Rute yang BISA diakses TANPA login (Public)
// ==========================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/admin/login', [AuthController::class, 'login']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/products/search', [ProductController::class, 'search']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// ==========================================
// Rute yang WAJIB LOGIN (Private / Protected)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {

    // Auth & Profile
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', function (Request $request) {
        return response()->json([
            'success' => true,
            'message' => 'Data profil berhasil diambil',
            'data' => $request->user()
        ]);
    });

    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/change-password', [AuthController::class, 'updatePassword']);

    // Cart & Wishlist
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::delete('/wishlist/{id}', [WishlistController::class, 'destroy']);

    // Orders & Payments (Pelanggan)
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/checkout', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/payments/{orderId}/proof', [PaymentController::class, 'uploadProof']);
    Route::put('/orders/{id}/cancel', [OrderController::class, 'cancel']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']); // Cadangan jika frontend menggunakan POST

    // --- RUTE KHUSUS ADMIN ---

    // Dashboard & Laporan
    Route::get('/admin/dashboard', [DashboardController::class, 'index']);
    Route::get('/admin/reports/sales', [ReportController::class, 'sales']);
    Route::get('/admin/reports/products', [ReportController::class, 'products']);

    // Manajemen Kategori
    Route::get('/admin/categories', [CategoryController::class, 'index']);
    Route::post('/admin/categories', [CategoryController::class, 'store']);
    Route::put('/admin/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/admin/categories/{id}', [CategoryController::class, 'destroy']);

    // Manajemen Produk
    Route::get('/admin/products', [ProductController::class, 'indexAdmin']);
    Route::post('/admin/products', [ProductController::class, 'store']);
    Route::put('/admin/products/{id}', [ProductController::class, 'update']);
    Route::delete('/admin/products/{id}', [ProductController::class, 'destroy']);
    Route::post('/admin/products/{id}/images', [ProductController::class, 'uploadImages']);

    // Manajemen Banner
    Route::get('/admin/banners', [BannerController::class, 'index']);
    Route::post('/admin/banners', [BannerController::class, 'store']);
    Route::put('/admin/banners/{id}', [BannerController::class, 'update']);
    Route::delete('/admin/banners/{id}', [BannerController::class, 'destroy']);
    Route::post('/admin/banners/{id}/image', [BannerController::class, 'uploadImage']);

    // Manajemen Pelanggan
    Route::get('/admin/users', [AdminUserController::class, 'index']);
    Route::delete('/admin/users/{id}', [AdminUserController::class, 'destroy']);

    // Manajemen Transaksi
    Route::get('/admin/orders', [OrderController::class, 'indexAdmin']);
    Route::put('/admin/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::put('/admin/payments/{id}/verify', [PaymentController::class, 'verify']);
    Route::put('/admin/payments/{id}/reject', [PaymentController::class, 'reject']);
});
