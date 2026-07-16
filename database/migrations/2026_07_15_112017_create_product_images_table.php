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
    Schema::create('product_images', function (Blueprint $table) {
        $table->id();
        // Relasi ke tabel produk. Jika produk dihapus, semua gambarnya ikut terhapus
        $table->foreignId('product_id')->constrained()->onDelete('cascade');
        $table->string('image_path'); // Untuk menyimpan nama/path file gambar
        $table->boolean('is_primary')->default(false); // Penanda apakah ini gambar utama (thumbnail)
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
