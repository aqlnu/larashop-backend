<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan kolom material & dimension yang dipakai form produk di admin frontend.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('material')->nullable()->after('weight');
            $table->string('dimension')->nullable()->after('material');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['material', 'dimension']);
        });
    }
};
