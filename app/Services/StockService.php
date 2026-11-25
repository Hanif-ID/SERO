<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Satu-satunya pintu untuk mengubah stok. Setiap perubahan:
 *  - dilakukan dalam transaksi + row lock (aman dari tabrakan),
 *  - tidak boleh membuat stok minus,
 *  - tidak boleh melebihi kapasitas lokasi,
 *  - otomatis tercatat di buku besar (stock_movements).
 */
class StockService
{
    public function change(
        Product $product,
        Location $location,
        int $delta,
        string $type,
        ?User $by = null,
        ?Model $ref = null,
        ?string $note = null,
        string $errorKey = 'qty',
    ): StockMovement {
        return DB::transaction(function () use ($product, $location, $delta, $type, $by, $ref, $note, $errorKey) {
            $stock = ProductStock::where('product_id', $product->id)
                ->where('location_id', $location->id)
                ->lockForUpdate()
                ->first() ?? ProductStock::create([
                    'product_id' => $product->id,
                    'location_id' => $location->id,
                    'qty' => 0,
                ]);

            $new = $stock->qty + $delta;

            if ($new < 0) {
                throw ValidationException::withMessages([
                    $errorKey => "Stok {$product->name} di {$location->name} tidak cukup (tersedia {$stock->qty}).",
                ]);
            }

            if ($delta > 0) {
                $free = $location->freeSpace();
                if ($free !== null && $delta > $free) {
                    throw ValidationException::withMessages([
                        $errorKey => "Kapasitas {$location->name} tidak cukup: sisa ruang {$free}, butuh {$delta}.",
                    ]);
                }
            }

            $stock->update(['qty' => $new]);

            return StockMovement::create([
                'product_id' => $product->id,
                'location_id' => $location->id,
                'user_id' => $by?->id,
                'type' => $type,
                'qty' => $delta,
                'balance_after' => $new,
                'reference_type' => $ref ? $ref::class : null,
                'reference_id' => $ref?->getKey(),
                'note' => $note,
            ]);
        });
    }

    /** Pindahkan stok antar lokasi (mis. freezer -> etalase). */
    public function transfer(Product $product, Location $from, Location $to, int $qty, ?User $by, ?string $note = null): void
    {
        DB::transaction(function () use ($product, $from, $to, $qty, $by, $note) {
            $this->change($product, $from, -$qty, 'transfer_out', $by, null, trim("Pindah ke {$to->name}. {$note}"));
            $this->change($product, $to, $qty, 'transfer_in', $by, null, trim("Pindah dari {$from->name}. {$note}"), 'to_location_id');
        });
    }

    /** Stok opname: samakan stok sistem dengan hasil hitung fisik. */
    public function adjustTo(Product $product, Location $location, int $counted, ?User $by, string $note): ?StockMovement
    {
        return DB::transaction(function () use ($product, $location, $counted, $by, $note) {
            $current = (int) ProductStock::where('product_id', $product->id)
                ->where('location_id', $location->id)
                ->lockForUpdate()
                ->value('qty');

            $delta = $counted - $current;
            if ($delta === 0) {
                return null;
            }

            return $this->change($product, $location, $delta, 'adjust', $by, null, $note, 'counted_qty');
        });
    }
}
