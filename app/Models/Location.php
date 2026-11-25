<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    public const TYPES = ['freezer' => 'Freezer (Gudang)', 'display' => 'Etalase (Area Penjualan)'];

    protected $fillable = ['name', 'type', 'capacity'];

    public function stocks() { return $this->hasMany(ProductStock::class); }

    public function usedUnits(): int
    {
        return (int) $this->stocks()->sum('qty');
    }

    /** Sisa ruang. null kapasitas = tak terbatas. */
    public function freeSpace(): ?int
    {
        return $this->capacity === null ? null : max(0, $this->capacity - $this->usedUnits());
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
