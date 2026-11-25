<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(array_keys(Location::TYPES))],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000000'],
        ];
    }

    public function index()
    {
        $locations = Location::withSum('stocks as used', 'qty')->orderBy('type')->orderBy('name')->get();

        return view('admin.locations.index', compact('locations'));
    }

    public function create()
    {
        return view('admin.locations.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Location::create($request->validate($this->rules()));

        return redirect()->route('admin.locations.index')->with('success', 'Lokasi berhasil ditambahkan.');
    }

    public function edit(Location $location)
    {
        return view('admin.locations.edit', compact('location'));
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $data = $request->validate($this->rules());

        if ($data['capacity'] !== null && $data['capacity'] < $location->usedUnits()) {
            return back()->withInput()->withErrors(['capacity' => 'Kapasitas tidak boleh lebih kecil dari isi saat ini ('.$location->usedUnits().').']);
        }

        $location->update($data);

        return redirect()->route('admin.locations.index')->with('success', 'Lokasi berhasil diperbarui.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        if ($location->usedUnits() > 0 || StockMovement::where('location_id', $location->id)->exists()) {
            return back()->with('error', 'Lokasi masih berisi stok atau punya riwayat, tidak bisa dihapus.');
        }

        $location->delete();

        return redirect()->route('admin.locations.index')->with('success', 'Lokasi berhasil dihapus.');
    }
}
