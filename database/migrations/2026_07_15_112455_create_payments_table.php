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
    Schema::create('payments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('order_id')->constrained()->onDelete('cascade');
        $table->string('payment_method'); // Contoh: Transfer Bank, E-Wallet
        $table->decimal('amount', 12, 2);
        $table->string('proof_of_payment')->nullable(); // Menyimpan nama file gambar bukti transfer
        // Status pembayaran WAJIB huruf kecil semua sesuai kontrak API
        $table->string('status')->default('pending'); 
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
