<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\OrderEvent;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\ReturnRequest;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $incoming = Product::incomingMap();
        $low = Product::withTotals()->with('vendor')->orderBy('name')->get()
            ->filter(fn ($p) => $p->isLow())
            ->each(fn ($p) => $p->incoming = (int) ($incoming[$p->id] ?? 0));

        $active = PurchaseOrder::with('vendor')->whereIn('status', PurchaseOrder::ACTIVE)->latest()->get();

        return view('admin.dashboard', [
            'low' => $low,
            'active' => $active,
            'lateResponse' => $active->filter->isResponseLate(),
            'lateShipment' => $active->filter->isShipmentLate(),
            'inInspection' => $active->where('status', 'diperiksa'),
            'openReturns' => ReturnRequest::whereIn('status', ReturnRequest::OPEN)->count(),
            'locations' => Location::withSum('stocks as used', 'qty')->orderBy('type')->orderBy('name')->get(),
            'events' => OrderEvent::with(['order', 'user'])->latest('id')->limit(8)->get(),
        ]);
    }
}
