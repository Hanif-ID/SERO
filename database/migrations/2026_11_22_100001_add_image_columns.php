<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $t) {
            $t->string('image')->nullable()->after('name');            // foto produk
        });
        Schema::table('users', function (Blueprint $t) {
            $t->string('avatar')->nullable()->after('email');          // foto profil
        });
        Schema::table('purchase_orders', function (Blueprint $t) {
            $t->string('delivery_note_photo')->nullable()->after('driver_info'); // foto nota/surat jalan dari vendor
        });
        Schema::table('return_request_items', function (Blueprint $t) {
            $t->string('photo')->nullable()->after('reason');          // foto bukti barang rusak
        });
    }

    public function down(): void
    {
        Schema::table('return_request_items', fn (Blueprint $t) => $t->dropColumn('photo'));
        Schema::table('purchase_orders', fn (Blueprint $t) => $t->dropColumn('delivery_note_photo'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('avatar'));
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn('image'));
    }
};
