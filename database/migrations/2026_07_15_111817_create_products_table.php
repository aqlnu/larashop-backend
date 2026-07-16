<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        // Menghubungkan ke tabel categories. Jika kategori dihapus, produk di dalamnya otomatis terhapus (cascade)
        $table->foreignId('category_id')->constrained()->onDelete('cascade'); 
        $table->string('name');
        $table->string('slug')->unique();
        $table->text('description')->nullable();
        $table->decimal('price', 12, 2); // Menggunakan decimal agar akurat untuk harga/keuangan
        $table->integer('stock')->default(0);
        $table->string('sku')->unique()->nullable(); // Kode unik stok barang
        $table->integer('weight')->default(0); // Berat barang dalam gram (penting untuk ongkir)
        $table->boolean('is_active')->default(true); // Status produk aktif/tidak
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
