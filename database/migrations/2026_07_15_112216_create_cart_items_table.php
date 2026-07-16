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
    Schema::create('cart_items', function (Blueprint $table) {
        $table->id();
        // Relasi: Item ini masuk ke keranjang yang mana
        $table->foreignId('cart_id')->constrained()->onDelete('cascade');
        // Relasi: Produk apa yang dimasukkan
        $table->foreignId('product_id')->constrained()->onDelete('cascade');
        $table->integer('quantity')->default(1); // Jumlah barang yang dibeli
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
