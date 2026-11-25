<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Buku besar stok: setiap perubahan stok tercatat di sini
        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained();
            $t->foreignId('location_id')->constrained();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type'); // in | transfer_in | transfer_out | adjust
            $t->integer('qty'); // bertanda: + masuk, - keluar
            $t->unsignedInteger('balance_after');
            $t->string('reference_type')->nullable();
            $t->unsignedBigInteger('reference_id')->nullable();
            $t->string('note')->nullable();
            $t->timestamps();
            $t->index(['product_id', 'location_id']);
        });

        Schema::create('return_requests', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $t->string('status')->default('diajukan')->index(); // diajukan | disetujui | ditolak | selesai
            $t->text('note')->nullable();
            $t->text('vendor_note')->nullable();
            $t->timestamp('responded_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('return_request_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('return_request_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained();
            $t->unsignedInteger('qty');
            $t->string('reason')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_request_items');
        Schema::dropIfExists('return_requests');
        Schema::dropIfExists('stock_movements');
    }
};
