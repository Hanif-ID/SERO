<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReturnRequest extends Model
{
    public const STATUS = [
        'diajukan' => ['Menunggu Respons Vendor', 'warning'],
        'disetujui' => ['Disetujui Vendor', 'info'],
        'ditolak' => ['Ditolak Vendor', 'danger'],
        'selesai' => ['Selesai', 'success'],
    ];

    public const OPEN = ['diajukan', 'disetujui'];

    protected $fillable = ['code', 'purchase_order_id', 'vendor_id', 'created_by', 'status', 'note', 'vendor_note', 'responded_at', 'completed_at'];

    protected function casts(): array
    {
        return ['responded_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function order() { return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id'); }
    public function vendor() { return $this->belongsTo(User::class, 'vendor_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function items() { return $this->hasMany(ReturnRequestItem::class); }

    public static function generateCode(): string
    {
        do {
            $code = 'RT-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function statusLabel(): string { return self::STATUS[$this->status][0] ?? $this->status; }
    public function statusColor(): string { return self::STATUS[$this->status][1] ?? 'secondary'; }
}
