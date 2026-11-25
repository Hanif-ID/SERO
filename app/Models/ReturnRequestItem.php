<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnRequestItem extends Model
{
    protected $fillable = ['return_request_id', 'product_id', 'qty', 'reason', 'photo'];

    public function photoUrl(): ?string
    {
        return \App\Services\ImageStore::url($this->photo);
    }

    public function product() { return $this->belongsTo(Product::class); }
}
