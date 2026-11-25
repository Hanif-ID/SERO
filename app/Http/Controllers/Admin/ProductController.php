<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\ImageStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    private function rules(?Product $product = null): array
    {
        return [
            'vendor_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_VENDOR)],
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product?->id)],
            'name' => ['required', 'string', 'max:255'],
            'image' => ImageStore::RULES,
            'unit' => ['required', 'string', 'max:20'],
            'min_stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'target_stock' => ['required', 'integer', 'gte:min_stock', 'max:1000000'],
        ];
    }

    public function index(Request $request)
    {
        $products = Product::withTotals()->with('vendor')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%")))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        return view('admin.products.create', ['vendors' => User::where('role', User::ROLE_VENDOR)->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $data['image'] = ImageStore::resolve($request, 'image', 'products', null);

        Product::create($data);

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        return view('admin.products.edit', [
            'product' => $product,
            'vendors' => User::where('role', User::ROLE_VENDOR)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate($this->rules($product));
        $data['image'] = ImageStore::resolve($request, 'image', 'products', $product->image);

        $product->update($data);

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if (PurchaseOrderItem::where('product_id', $product->id)->exists()
            || StockMovement::where('product_id', $product->id)->exists()) {
            return back()->with('error', 'Produk sudah punya riwayat pesanan/stok, tidak bisa dihapus.');
        }

        ImageStore::delete($product->image);
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dihapus.');
    }
}
