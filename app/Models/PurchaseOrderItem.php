<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = ['purchase_order_id', 'product_id', 'qty_ordered', 'qty_prepared', 'qty_received', 'qty_rejected', 'reject_reason'];

    public function order() { return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id'); }
    public function product() { return $this->belongsTo(Product::class); }

    /** Jumlah baik yang masuk stok. */
    public function qtyGood(): int
    {
        return max(0, (int) $this->qty_received - (int) $this->qty_rejected);
    }
}
