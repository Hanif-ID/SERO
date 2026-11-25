<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PurchaseOrder extends Model
{
    public const STATUS = [
        'menunggu' => ['Menunggu Konfirmasi', 'warning'],
        'dikonfirmasi' => ['Dikonfirmasi Vendor', 'info'],
        'ditolak' => ['Ditolak Vendor', 'danger'],
        'disiapkan' => ['Disiapkan Vendor', 'primary'],
        'dikirim' => ['Dalam Pengiriman', 'primary'],
        'diperiksa' => ['Sedang Diperiksa', 'warning'],
        'selesai' => ['Selesai', 'success'],
        'dibatalkan' => ['Dibatalkan', 'secondary'],
    ];

    /** Status yang masih berjalan (barang belum masuk stok). */
    public const ACTIVE = ['menunggu', 'dikonfirmasi', 'disiapkan', 'dikirim', 'diperiksa'];

    public const RESPONSE_SLA_HOURS = 4;      // vendor dianggap lambat jika belum merespons lebih dari ini
    public const INSPECTION_MAX_MINUTES = 30; // pemeriksaan lebih lama dari ini berisiko mencairkan barang
    public const FROZEN_MAX_TEMP = -18;       // suhu di atas ini dianggap tidak aman untuk frozen food

    protected $fillable = [
        'code', 'vendor_id', 'created_by', 'status', 'needed_date', 'notes',
        'vendor_responded_at', 'vendor_note', 'expected_arrival_at', 'shipped_at', 'driver_info', 'delivery_note_photo', 'ship_temp',
        'arrived_at', 'checked_at', 'arrival_temp', 'receive_location_id', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'needed_date' => 'date',
            'vendor_responded_at' => 'datetime',
            'expected_arrival_at' => 'datetime',
            'shipped_at' => 'datetime',
            'arrived_at' => 'datetime',
            'checked_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'ship_temp' => 'float',
            'arrival_temp' => 'float',
        ];
    }

    public function vendor() { return $this->belongsTo(User::class, 'vendor_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function items() { return $this->hasMany(PurchaseOrderItem::class); }
    public function events() { return $this->hasMany(OrderEvent::class)->latest('id'); }
    public function receiveLocation() { return $this->belongsTo(Location::class, 'receive_location_id'); }
    public function returnRequest() { return $this->hasOne(ReturnRequest::class); }

    public static function generateCode(): string
    {
        do {
            $code = 'PO-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function deliveryNoteUrl(): ?string
    {
        return \App\Services\ImageStore::url($this->delivery_note_photo);
    }

    public function statusLabel(): string { return self::STATUS[$this->status][0] ?? $this->status; }
    public function statusColor(): string { return self::STATUS[$this->status][1] ?? 'secondary'; }

    public function addEvent(string $note, ?User $by = null, string $type = 'status', ?string $status = null): OrderEvent
    {
        return $this->events()->create([
            'user_id' => $by?->id,
            'type' => $type,
            'status' => $status,
            'note' => $note,
        ]);
    }

    /** Vendor belum merespons melebihi batas waktu. */
    public function isResponseLate(): bool
    {
        return $this->status === 'menunggu'
            && $this->created_at->lt(now()->subHours(self::RESPONSE_SLA_HOURS));
    }

    /** Pengiriman terlambat dari jadwal. */
    public function isShipmentLate(): bool
    {
        if ($this->status === 'dikirim' && $this->expected_arrival_at) {
            return $this->expected_arrival_at->isPast();
        }
        if (in_array($this->status, ['dikonfirmasi', 'disiapkan'], true)) {
            return $this->needed_date->lt(today());
        }

        return false;
    }

    public function isTempUnsafe(?float $temp): bool
    {
        return $temp !== null && $temp > self::FROZEN_MAX_TEMP;
    }

    public function inspectionMinutes(): ?int
    {
        if (! $this->arrived_at) {
            return null;
        }

        return (int) $this->arrived_at->diffInMinutes($this->checked_at ?? now());
    }
}
