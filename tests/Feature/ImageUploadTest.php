<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $vendor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('uploads');
        $this->admin = User::factory()->admin()->create();
        $this->vendor = User::factory()->create();
    }

    private function productData(array $extra = []): array
    {
        return $extra + ['vendor_id' => $this->vendor->id, 'sku' => 'IMG-1', 'name' => 'Nugget', 'unit' => 'pak', 'min_stock' => 5, 'target_stock' => 20];
    }

    public function test_product_image_upload_replace_remove_and_delete(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/products', $this->productData(['image' => UploadedFile::fake()->image('a.jpg', 300, 300)]))
            ->assertRedirect('/admin/products');
        $product = Product::firstOrFail();
        $this->assertStringStartsWith('products/', $product->image);
        Storage::disk('uploads')->assertExists($product->image);
        $this->assertStringContainsString('uploads/'.$product->image, $product->imageUrl());

        // ganti gambar -> file lama terhapus
        $old = $product->image;
        $this->put("/admin/products/{$product->id}", $this->productData(['image' => UploadedFile::fake()->image('b.png', 200, 200)]))
            ->assertRedirect('/admin/products');
        $product->refresh();
        Storage::disk('uploads')->assertMissing($old);
        Storage::disk('uploads')->assertExists($product->image);

        // simpan tanpa file -> gambar dipertahankan
        $kept = $product->image;
        $this->put("/admin/products/{$product->id}", $this->productData())->assertRedirect('/admin/products');
        $this->assertSame($kept, $product->fresh()->image);

        // centang hapus -> gambar hilang, tampil placeholder
        $this->put("/admin/products/{$product->id}", $this->productData(['remove_image' => '1']))->assertRedirect('/admin/products');
        $this->assertNull($product->fresh()->image);
        Storage::disk('uploads')->assertMissing($kept);
        $this->assertStringContainsString('placeholder-product.svg', $product->fresh()->imageUrl());

        // hapus produk -> file ikut terhapus
        $this->put("/admin/products/{$product->id}", $this->productData(['image' => UploadedFile::fake()->image('c.jpg')]));
        $path = $product->fresh()->image;
        $this->delete("/admin/products/{$product->id}")->assertRedirect('/admin/products');
        Storage::disk('uploads')->assertMissing($path);
    }

    public function test_rejects_non_images_svg_and_oversized_files(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/products', $this->productData(['image' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]))->assertSessionHasErrors('image');
        $this->post('/admin/products', $this->productData(['image' => UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')]))->assertSessionHasErrors('image');
        $this->post('/admin/products', $this->productData(['image' => UploadedFile::fake()->image('big.jpg')->size(3000)]))->assertSessionHasErrors('image');

        $this->assertSame(0, Product::count());
        $this->assertSame([], Storage::disk('uploads')->allFiles());
    }

    public function test_user_avatar_upload_and_navbar(): void
    {
        $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'Berfoto', 'email' => 'foto@example.com', 'role' => 'vendor',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'avatar' => UploadedFile::fake()->image('me.jpg', 120, 120),
        ])->assertRedirect('/admin/users');

        $u = User::where('email', 'foto@example.com')->firstOrFail();
        Storage::disk('uploads')->assertExists($u->avatar);

        $this->actingAs($u)->get('/vendor/dashboard')->assertOk()->assertSee('uploads/'.$u->avatar, false);
        $this->actingAs($this->admin)->get('/admin/users')->assertOk()->assertSee('uploads/'.$u->avatar, false);

        $this->delete("/admin/users/{$u->id}");
        Storage::disk('uploads')->assertMissing($u->avatar);
    }

    private function inspectingOrder(array $quantities): array
    {
        $freezer = Location::create(['name' => 'Freezer', 'type' => 'freezer', 'capacity' => 100]);
        $order = PurchaseOrder::create(['code' => 'PO-IMG', 'vendor_id' => $this->vendor->id, 'created_by' => $this->admin->id,
            'status' => 'diperiksa', 'needed_date' => today(), 'arrived_at' => now()]);
        $items = [];
        foreach ($quantities as $i => $qty) {
            $p = Product::create($this->productData(['sku' => "S{$i}", 'name' => "Produk {$i}"]));
            $items[] = $order->items()->create(['product_id' => $p->id, 'qty_ordered' => $qty]);
        }

        return [$order, $items, $freezer];
    }

    public function test_return_evidence_photo_is_saved_and_visible_to_vendor(): void
    {
        [$order, [$item], $freezer] = $this->inspectingOrder([10]);

        $this->actingAs($this->admin)->post("/admin/orders/{$order->id}/confirm", [
            'receive_location_id' => $freezer->id,
            'items' => [$item->id => ['qty_received' => 10, 'qty_rejected' => 2, 'reject_reason' => 'Kemasan bocor', 'photo' => UploadedFile::fake()->image('bukti.jpg', 400, 300)]],
        ])->assertSessionDoesntHaveErrors();

        $ri = ReturnRequest::firstOrFail()->items->first();
        $this->assertStringStartsWith('returns/', $ri->photo);
        Storage::disk('uploads')->assertExists($ri->photo);

        $this->actingAs($this->vendor)->get('/vendor/returns/'.ReturnRequest::first()->id)->assertOk()->assertSee('uploads/'.$ri->photo, false);
    }

    public function test_failed_confirmation_leaves_no_orphan_photos(): void
    {
        // item 1 rusak + foto (tersimpan lebih dulu), item 2 melebihi kapasitas freezer -> semua dibatalkan
        [$order, [$a, $b], $freezer] = $this->inspectingOrder([5, 500]);

        $this->actingAs($this->admin)->post("/admin/orders/{$order->id}/confirm", [
            'receive_location_id' => $freezer->id,
            'items' => [
                $a->id => ['qty_received' => 5, 'qty_rejected' => 5, 'reject_reason' => 'Rusak', 'photo' => UploadedFile::fake()->image('x.jpg')],
                $b->id => ['qty_received' => 500, 'qty_rejected' => 0],
            ],
        ])->assertSessionHasErrors('receive_location_id');

        $this->assertSame('diperiksa', $order->fresh()->status);
        $this->assertSame(0, ReturnRequest::count());
        $this->assertSame([], Storage::disk('uploads')->allFiles('returns'));
    }

    public function test_delivery_note_photo_on_shipping(): void
    {
        $order = PurchaseOrder::create(['code' => 'PO-NOTA', 'vendor_id' => $this->vendor->id, 'created_by' => $this->admin->id, 'status' => 'disiapkan', 'needed_date' => today()]);

        $this->actingAs($this->vendor)->post("/vendor/orders/{$order->id}/ship", [
            'expected_arrival_at' => now()->addHours(4)->format('Y-m-d\TH:i'), 'driver_info' => 'Budi B1234',
            'delivery_note' => UploadedFile::fake()->image('nota.jpg', 500, 700),
        ])->assertSessionDoesntHaveErrors();

        $order->refresh();
        $this->assertStringStartsWith('notes/', $order->delivery_note_photo);
        Storage::disk('uploads')->assertExists($order->delivery_note_photo);

        $this->actingAs($this->admin)->get("/admin/orders/{$order->id}")->assertOk()->assertSee('uploads/'.$order->delivery_note_photo, false);
    }

    public function test_thumbnails_appear_in_lists(): void
    {
        $p = Product::create($this->productData(['image' => 'products/demo.jpg']));

        $this->actingAs($this->admin);
        foreach (['/admin/products', '/admin/stock', '/admin/restock', '/admin/dashboard', '/admin/orders/create?vendor_id='.$this->vendor->id] as $url) {
            $this->get($url)->assertOk()->assertSee('uploads/products/demo.jpg', false);
        }
    }
}
