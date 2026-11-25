<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Product extends Model
{
    protected $fillable = ['vendor_id', 'sku', 'name', 'image', 'unit', 'min_stock', 'target_stock'];

    public function vendor() { return $this->belongsTo(User::class, 'vendor_id'); }

    public function imageUrl(): string
    {
        return \App\Services\ImageStore::url($this->image, 'assets/img/placeholder-product.svg');
    }

    public function stocks() { return $this->hasMany(ProductStock::class); }
    public function movements() { return $this->hasMany(StockMovement::class); }

    /** Menambahkan kolom total_stock (jumlah di semua lokasi). */
    public function scopeWithTotals(Builder $q): Builder
    {
        return $q->withSum('stocks as total_stock', 'qty');
    }

    public function total(): int
    {
        return (int) ($this->total_stock ?? $this->stocks()->sum('qty'));
    }

    /** Total stok terbaru langsung dari database (abaikan nilai cache withSum). */
    public function freshTotal(): int
    {
        return (int) $this->stocks()->sum('qty');
    }

    public function isLow(): bool
    {
        return $this->total() <= $this->min_stock;
    }

    /** Jumlah yang sedang dipesan (belum diterima) per produk: [product_id => qty]. */
    public static function incomingMap(): Collection
    {
        return PurchaseOrderItem::query()
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->whereIn('purchase_orders.status', PurchaseOrder::ACTIVE)
            ->selectRaw('purchase_order_items.product_id as pid, SUM(purchase_order_items.qty_ordered) as qty')
            ->groupBy('purchase_order_items.product_id')
            ->pluck('qty', 'pid');
    }

    /** Saran jumlah pesan = target - stok sekarang - yang sudah dalam pesanan. */
    public function suggestedQty(int $incoming = 0): int
    {
        return max(0, $this->target_stock - $this->total() - $incoming);
    }
}
