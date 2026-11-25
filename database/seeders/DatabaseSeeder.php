<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Akun contoh (password: "password"):
     *   admin@sero.test   -> admin (toko)
     *   vendor@sero.test  -> vendor 1
     *   vendor2@sero.test -> vendor 2
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(['email' => 'admin@sero.test'],
            ['name' => 'Admin Sero', 'password' => 'password', 'role' => User::ROLE_ADMIN, 'email_verified_at' => now()]);
        $v1 = User::updateOrCreate(['email' => 'vendor@sero.test'],
            ['name' => 'PT Beku Jaya', 'password' => 'password', 'role' => User::ROLE_VENDOR, 'email_verified_at' => now()]);
        $v2 = User::updateOrCreate(['email' => 'vendor2@sero.test'],
            ['name' => 'CV Dingin Sejuk', 'password' => 'password', 'role' => User::ROLE_VENDOR, 'email_verified_at' => now()]);

        $freezerA = Location::firstOrCreate(['name' => 'Freezer A'], ['type' => 'freezer', 'capacity' => 300]);
        $freezerB = Location::firstOrCreate(['name' => 'Freezer B'], ['type' => 'freezer', 'capacity' => 200]);
        Location::firstOrCreate(['name' => 'Etalase Frozen Snack'], ['type' => 'display', 'capacity' => 100]);
        Location::firstOrCreate(['name' => 'Etalase Es Krim'], ['type' => 'display', 'capacity' => 80]);

        // sku => [vendor, nama, satuan, min, target, stok awal, lokasi]
        $catalog = [
            'NGT-500' => [$v1, 'Nugget Ayam 500g', 'pak', 20, 80, 12, $freezerA],
            'SSG-1000' => [$v1, 'Sosis Sapi 1kg', 'pak', 15, 60, 40, $freezerA],
            'DMS-300' => [$v1, 'Dimsum Ayam 300g', 'pak', 10, 40, 5, $freezerB],
            'ESK-VAN1L' => [$v2, 'Es Krim Vanilla 1L', 'tub', 10, 40, 25, $freezerB],
            'KTG-1000' => [$v2, 'Kentang Goreng 1kg', 'pak', 12, 50, 8, $freezerA],
            'BKS-500' => [$v2, 'Bakso Sapi 500g', 'pak', 15, 60, 30, $freezerA],
        ];

        $stock = app(StockService::class);
        foreach ($catalog as $sku => [$vendor, $name, $unit, $min, $target, $initial, $loc]) {
            $product = Product::updateOrCreate(['sku' => $sku], [
                'vendor_id' => $vendor->id, 'name' => $name, 'unit' => $unit, 'min_stock' => $min, 'target_stock' => $target,
            ]);

            if (! ProductStock::where('product_id', $product->id)->exists() && $initial > 0) {
                $stock->change($product, $loc, $initial, 'adjust', $admin, null, 'Stok awal');
            }
        }
    }
}
