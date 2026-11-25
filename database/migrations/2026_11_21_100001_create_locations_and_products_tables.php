<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lokasi penyimpanan: freezer (gudang) atau display (area penjualan/etalase)
        Schema::create('locations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('type')->default('freezer'); // freezer | display
            $t->unsignedInteger('capacity')->nullable(); // kapasitas (satuan unit), null = tak terbatas
            $t->timestamps();
        });

        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $t->string('sku')->unique();
            $t->string('name');
            $t->string('unit')->default('pcs');
            $t->unsignedInteger('min_stock')->default(0);    // batas minimum -> memicu "perlu restock"
            $t->unsignedInteger('target_stock')->default(0); // target stok ideal
            $t->timestamps();
        });

        // Stok per produk per lokasi
        Schema::create('product_stocks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('location_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('qty')->default(0);
            $t->timestamps();
            $t->unique(['product_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
        Schema::dropIfExists('products');
        Schema::dropIfExists('locations');
    }
};
