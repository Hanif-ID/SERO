<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderEvent extends Model
{
    protected $fillable = ['purchase_order_id', 'user_id', 'type', 'status', 'note'];

    public function order() { return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
