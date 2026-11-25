<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    public const TYPES = [
        'in' => ['Barang Masuk', 'success'],
        'transfer_in' => ['Pindah Masuk', 'info'],
        'transfer_out' => ['Pindah Keluar', 'warning'],
        'adjust' => ['Penyesuaian', 'secondary'],
    ];

    protected $fillable = ['product_id', 'location_id', 'user_id', 'type', 'qty', 'balance_after', 'reference_type', 'reference_id', 'note'];

    public function product() { return $this->belongsTo(Product::class); }
    public function location() { return $this->belongsTo(Location::class); }
    public function user() { return $this->belongsTo(User::class); }

    public function typeLabel(): string { return self::TYPES[$this->type][0] ?? $this->type; }
    public function typeColor(): string { return self::TYPES[$this->type][1] ?? 'secondary'; }
}
