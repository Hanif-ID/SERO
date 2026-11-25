<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
  <div class="app-brand demo">
    <a href="{{ url('/') }}" class="app-brand-link">
      <span class="app-brand-logo demo">
        @include('layouts.logo')
      </span>
      <span class="app-brand-text demo menu-text fw-bolder ms-2">Sero</span>
    </a>
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
      <i class="bx bx-chevron-left bx-sm align-middle"></i>
    </a>
  </div>
  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    <li class="menu-item {{ request()->routeIs('admin.dashboard', 'vendor.dashboard') ? 'active' : '' }}">
      <a href="{{ route(auth()->user()->homeRoute()) }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-home-circle"></i>
        <div>Dashboard</div>
      </a>
    </li>

    @if (auth()->user()->isAdmin())
      <li class="menu-header small text-uppercase"><span class="menu-header-text">Alur Pengadaan</span></li>
      <li class="menu-item {{ request()->routeIs('admin.restock.*') ? 'active' : '' }}">
        <a href="{{ route('admin.restock.index') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-error-circle"></i><div>Kebutuhan Stok</div>
        </a>
      </li>
      <li class="menu-item {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
        <a href="{{ route('admin.orders.index') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-receipt"></i><div>Pesanan Vendor</div>
        </a>
      </li>
      <li class="menu-item {{ request()->routeIs('admin.returns.*') ? 'active' : '' }}">
        <a href="{{ route('admin.returns.index') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-undo"></i><div>Retur</div>
        </a>
      </li>

      <li class="menu-header small text-uppercase"><span class="menu-header-text">Stok &amp; Penataan</span></li>
      <li class="menu-item {{ request()->routeIs('admin.stock.index') ? 'active' : '' }}">
        <a href="{{ route('admin.stock.index') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-package"></i><div>Stok &amp; Lokasi</div>
        </a>
      </li>
      <li class="menu-item {{ request()->routeIs('admin.stock.manage') ? 'active' : '' }}">
        <a href="{{ route('admin.stock.manage') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-transfer"></i><div>Pindah &amp; Opname</div>
        </a>
      </li>
      <li class="menu-item {{ request()->routeIs('admin.stock.movements') ? 'active' : '' }}">
        <a href="{{ route('admin.stock.movements') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-history"></i><div>Riwayat Stok</div>
        </a>
      </li>

      <li class="menu-header small text-uppercase"><span class="menu-header-text">Master Data</span></li>
      <li class="menu-item {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
        <a href="{{ route('admin.products.index') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-box"></i><div>Produk</div>
        </a>
      </li>
      <li class="menu-item {{ request()->routeIs('admin.locations.*') ? 'active' : '' }}">
        <a href="{{ route('admin.locations.index') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-map"></i><div>Lokasi Penyimpanan</div>
        </a>
      </li>
      <li class="menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
        <a href="{{ route('admin.users.index') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-group"></i><div>Kelola User</div>
        </a>
      </li>
    @else
      <li class="menu-header small text-uppercase"><span class="menu-header-text">Vendor</span></li>
      <li class="menu-item {{ request()->routeIs('vendor.orders.*') ? 'active' : '' }}">
        <a href="{{ route('vendor.orders.index') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-receipt"></i><div>Pesanan Masuk</div>
        </a>
      </li>
      <li class="menu-item {{ request()->routeIs('vendor.returns.*') ? 'active' : '' }}">
        <a href="{{ route('vendor.returns.index') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-undo"></i><div>Retur</div>
        </a>
      </li>
    @endif
  </ul>
</aside>
