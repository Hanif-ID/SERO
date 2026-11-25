<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $t->string('status')->default('menunggu')->index();
            $t->date('needed_date');
            $t->text('notes')->nullable();

            // Respons vendor
            $t->timestamp('vendor_responded_at')->nullable();
            $t->text('vendor_note')->nullable();

            // Pengiriman
            $t->timestamp('expected_arrival_at')->nullable();
            $t->timestamp('shipped_at')->nullable();
            $t->string('driver_info')->nullable();
            $t->decimal('ship_temp', 5, 1)->nullable();

            // Penerimaan & pemeriksaan
            $t->timestamp('arrived_at')->nullable();
            $t->timestamp('checked_at')->nullable();
            $t->decimal('arrival_temp', 5, 1)->nullable();
            $t->foreignId('receive_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $t->timestamp('confirmed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained();
            $t->unsignedInteger('qty_ordered');
            $t->unsignedInteger('qty_prepared')->nullable(); // diisi vendor
            $t->unsignedInteger('qty_received')->nullable(); // diisi karyawan saat periksa
            $t->unsignedInteger('qty_rejected')->default(0); // rusak / tidak sesuai
            $t->string('reject_reason')->nullable();
            $t->timestamps();
        });

        // Riwayat status + update posisi pengiriman
        Schema::create('order_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type')->default('status'); // status | tracking | note
            $t->string('status')->nullable();
            $t->text('note')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_events');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
