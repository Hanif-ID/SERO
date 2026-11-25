<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\ReturnRequest;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $vendor;
    private Location $freezer;
    private Location $display;
    private Product $nugget;
    private Product $sosis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->vendor = User::factory()->create(['name' => 'Vendor A']);
        $this->freezer = Location::create(['name' => 'Freezer A', 'type' => 'freezer', 'capacity' => 100]);
        $this->display = Location::create(['name' => 'Etalase', 'type' => 'display', 'capacity' => 20]);
        $this->nugget = Product::create(['vendor_id' => $this->vendor->id, 'sku' => 'NGT', 'name' => 'Nugget', 'unit' => 'pak', 'min_stock' => 10, 'target_stock' => 50]);
        $this->sosis = Product::create(['vendor_id' => $this->vendor->id, 'sku' => 'SSG', 'name' => 'Sosis', 'unit' => 'pak', 'min_stock' => 5, 'target_stock' => 30]);
        app(StockService::class)->change($this->nugget, $this->freezer, 4, 'adjust', $this->admin, null, 'Stok awal');
    }

    public function test_full_flow_from_restock_to_stock_update_and_return(): void
    {
        // 1. Kebutuhan stok: nugget (4 <= 10) dan sosis (0 <= 5) muncul, saran = target - stok
        $this->actingAs($this->admin)->get('/admin/restock')
            ->assertOk()->assertSee('Nugget')->assertSee('46')->assertSee('30');

        // 2. Pesan ke vendor
        $this->post('/admin/orders', [
            'vendor_id' => $this->vendor->id, 'needed_date' => today()->addDays(2)->toDateString(),
            'items' => [$this->nugget->id => 46, $this->sosis->id => 30],
        ])->assertRedirect();
        $order = PurchaseOrder::firstOrFail();
        $this->assertSame('menunggu', $order->status);

        // pesanan berjalan dihitung sebagai "sudah dipesan" -> saran jadi 0 (tidak pesan dobel)
        $this->get('/admin/restock')->assertSee('Semua sudah dalam pesanan');

        // 2b. Vendor konfirmasi
        $this->actingAs($this->vendor)->post("/vendor/orders/{$order->id}/respond", ['decision' => 'confirm'])->assertRedirect();
        $this->assertSame('dikonfirmasi', $order->fresh()->status);

        // 3. Siapkan: selisih tanpa alasan ditolak
        $items = $order->items->keyBy('product_id');
        $prep = [$items[$this->nugget->id]->id => 40, $items[$this->sosis->id]->id => 30];
        $this->post("/vendor/orders/{$order->id}/prepare", ['items' => $prep])->assertSessionHasErrors('prepare_note');
        $this->post("/vendor/orders/{$order->id}/prepare", ['items' => $prep, 'prepare_note' => 'Stok nugget kurang'])->assertSessionDoesntHaveErrors();
        $this->assertSame('disiapkan', $order->fresh()->status);

        // 3b. Kirim + update posisi
        $this->post("/vendor/orders/{$order->id}/ship", [
            'expected_arrival_at' => now()->addHours(5)->format('Y-m-d\TH:i'), 'driver_info' => 'Budi B1234', 'ship_temp' => -19,
        ])->assertSessionDoesntHaveErrors();
        $this->post("/vendor/orders/{$order->id}/track", ['note' => 'Tol KM 40'])->assertSessionDoesntHaveErrors();
        $this->actingAs($this->admin)->get("/admin/orders/{$order->id}")->assertOk()->assertSee('Tol KM 40');

        // 4. Barang tiba -> periksa
        $this->post("/admin/orders/{$order->id}/inspect")->assertRedirect();
        $this->assertSame('diperiksa', $order->fresh()->status);

        // 5+6. Konfirmasi: nugget 40 diterima, 5 rusak; sosis 30 baik
        $items = $order->fresh()->items->keyBy('product_id');
        $this->post("/admin/orders/{$order->id}/confirm", [
            'receive_location_id' => $this->freezer->id, 'arrival_temp' => -17,
            'items' => [
                $items[$this->nugget->id]->id => ['qty_received' => 40, 'qty_rejected' => 5, 'reject_reason' => 'Kemasan bocor'],
                $items[$this->sosis->id]->id => ['qty_received' => 30, 'qty_rejected' => 0],
            ],
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('selesai', $order->fresh()->status);
        $this->assertSame(4 + 35, $this->nugget->freshTotal());
        $this->assertSame(30, $this->sosis->freshTotal());
        $this->assertSame(2, StockMovement::where('type', 'in')->count());

        // retur otomatis hanya untuk barang rusak
        $retur = ReturnRequest::firstOrFail();
        $this->assertSame('diajukan', $retur->status);
        $this->assertSame(5, $retur->items->first()->qty);

        // Vendor menyetujui, toko menyelesaikan
        $this->actingAs($this->vendor)->post("/vendor/returns/{$retur->id}/respond", ['decision' => 'approve'])->assertRedirect();
        $this->actingAs($this->admin)->post("/admin/returns/{$retur->id}/complete")->assertRedirect();
        $this->assertSame('selesai', $retur->fresh()->status);

        // 7. Pindah ke etalase; kapasitas etalase 20
        $this->post('/admin/stock/transfer', ['product_id' => $this->nugget->id, 'from_location_id' => $this->freezer->id, 'to_location_id' => $this->display->id, 'qty' => 15])
            ->assertSessionDoesntHaveErrors();
        $this->assertSame(15, $this->display->usedUnits());
        $this->post('/admin/stock/transfer', ['product_id' => $this->nugget->id, 'from_location_id' => $this->freezer->id, 'to_location_id' => $this->display->id, 'qty' => 10])
            ->assertSessionHasErrors('to_location_id'); // melebihi kapasitas
        $this->assertSame(15, $this->display->usedUnits());

        // Halaman-halaman render
        foreach (['/admin/dashboard', '/admin/stock', '/admin/stock/movements', '/admin/stock/manage', '/admin/orders', "/admin/orders/{$order->id}", '/admin/returns', "/admin/returns/{$retur->id}", '/admin/products', '/admin/locations'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->actingAs($this->vendor);
        foreach (['/vendor/dashboard', '/vendor/orders', "/vendor/orders/{$order->id}", '/vendor/returns', "/vendor/returns/{$retur->id}"] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_stock_cannot_go_negative_or_exceed_capacity(): void
    {
        $svc = app(StockService::class);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $svc->change($this->nugget, $this->freezer, -99, 'adjust', $this->admin);
    }

    public function test_receiving_over_capacity_rolls_back_everything(): void
    {
        $order = PurchaseOrder::create(['code' => 'PO-X', 'vendor_id' => $this->vendor->id, 'created_by' => $this->admin->id, 'status' => 'diperiksa', 'needed_date' => today(), 'arrived_at' => now()]);
        $item = $order->items()->create(['product_id' => $this->nugget->id, 'qty_ordered' => 500]);

        $this->actingAs($this->admin)->post("/admin/orders/{$order->id}/confirm", [
            'receive_location_id' => $this->freezer->id,
            'items' => [$item->id => ['qty_received' => 500, 'qty_rejected' => 0]],
        ])->assertSessionHasErrors('receive_location_id');

        $this->assertSame('diperiksa', $order->fresh()->status);
        $this->assertSame(4, $this->nugget->freshTotal());
    }

    public function test_rejected_requires_reason_and_cannot_exceed_received(): void
    {
        $order = PurchaseOrder::create(['code' => 'PO-Y', 'vendor_id' => $this->vendor->id, 'created_by' => $this->admin->id, 'status' => 'diperiksa', 'needed_date' => today(), 'arrived_at' => now()]);
        $item = $order->items()->create(['product_id' => $this->nugget->id, 'qty_ordered' => 10]);

        $this->actingAs($this->admin)->post("/admin/orders/{$order->id}/confirm", [
            'receive_location_id' => $this->freezer->id,
            'items' => [$item->id => ['qty_received' => 10, 'qty_rejected' => 2]],
        ])->assertSessionHasErrors("items.{$item->id}.reject_reason");

        $this->post("/admin/orders/{$order->id}/confirm", [
            'receive_location_id' => $this->freezer->id,
            'items' => [$item->id => ['qty_received' => 3, 'qty_rejected' => 5, 'reject_reason' => 'x']],
        ])->assertSessionHasErrors("items.{$item->id}.qty_rejected");
    }

    public function test_vendor_cannot_touch_other_vendors_data_or_admin_pages(): void
    {
        $other = User::factory()->create();
        $order = PurchaseOrder::create(['code' => 'PO-Z', 'vendor_id' => $other->id, 'created_by' => $this->admin->id, 'status' => 'menunggu', 'needed_date' => today()]);

        $this->actingAs($this->vendor);
        $this->get("/vendor/orders/{$order->id}")->assertForbidden();
        $this->post("/vendor/orders/{$order->id}/respond", ['decision' => 'confirm'])->assertForbidden();
        $this->get('/admin/orders')->assertForbidden();
        $this->get('/admin/products')->assertForbidden();
        $this->get('/admin/stock')->assertForbidden();
        $this->assertSame('menunggu', $order->fresh()->status);

        $this->actingAs($this->admin)->get('/vendor/orders')->assertForbidden();
    }

    public function test_cannot_order_products_of_another_vendor(): void
    {
        $other = User::factory()->create();
        $foreign = Product::create(['vendor_id' => $other->id, 'sku' => 'F', 'name' => 'Asing', 'unit' => 'pcs', 'min_stock' => 1, 'target_stock' => 5]);

        $this->actingAs($this->admin)->post('/admin/orders', [
            'vendor_id' => $this->vendor->id, 'needed_date' => today()->toDateString(), 'items' => [$foreign->id => 5],
        ])->assertSessionHasErrors('items');
        $this->assertSame(0, PurchaseOrder::count());
    }

    public function test_opname_and_product_crud(): void
    {
        $this->actingAs($this->admin)->post('/admin/stock/adjust', [
            'product_id' => $this->nugget->id, 'location_id' => $this->freezer->id, 'counted_qty' => 9, 'note' => 'Opname',
        ])->assertSessionDoesntHaveErrors();
        $this->assertSame(9, $this->nugget->freshTotal());
        $this->assertSame(5, StockMovement::where('type', 'adjust')->latest('id')->value('qty'));

        $this->post('/admin/products', ['vendor_id' => $this->vendor->id, 'sku' => 'NEW', 'name' => 'Baru', 'unit' => 'pak', 'min_stock' => 5, 'target_stock' => 2])
            ->assertSessionHasErrors('target_stock');
        $this->post('/admin/products', ['vendor_id' => $this->vendor->id, 'sku' => 'NEW', 'name' => 'Baru', 'unit' => 'pak', 'min_stock' => 5, 'target_stock' => 20])
            ->assertRedirect('/admin/products');
        $this->delete('/admin/products/'.$this->nugget->id)->assertSessionHas('error'); // punya riwayat stok
    }
}
