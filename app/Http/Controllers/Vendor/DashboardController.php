<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $id = $request->user()->id;
        $orders = PurchaseOrder::where('vendor_id', $id)->whereIn('status', PurchaseOrder::ACTIVE)->withCount('items')->latest()->get();

        return view('vendor-area.dashboard', [
            'orders' => $orders,
            'needResponse' => $orders->where('status', 'menunggu')->count(),
            'needPrepare' => $orders->where('status', 'dikonfirmasi')->count(),
            'needShip' => $orders->where('status', 'disiapkan')->count(),
            'shipping' => $orders->where('status', 'dikirim')->count(),
            'returnsPending' => ReturnRequest::where('vendor_id', $id)->where('status', 'diajukan')->count(),
        ]);
    }
}
