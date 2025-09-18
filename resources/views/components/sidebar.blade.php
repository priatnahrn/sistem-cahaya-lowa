{{-- resources/views/components/sidebar.blade.php (responsive + collapsed icon-only, no chevrons) --}}
@php
    $brand = '#4BAC87';
    $navBG = '#344579';
    $is = fn($p) => request()->routeIs($p);
    $open = fn($ar) => collect($ar)->contains(fn($p) => request()->routeIs($p));
@endphp

<aside x-data="{ mobileOpen:false, collapsed:false }" class="relative">

  {{-- MOBILE: hamburger absolute --}}
  <div class="md:hidden absolute top-4 left-4 z-50">
    <button @click="mobileOpen = true" aria-label="Open menu"
            class="p-2 rounded-md bg-white/90 text-slate-700 shadow">
      <i class="fa-solid fa-bars"></i>
    </button>
  </div>

  {{-- OFF-CANVAS MOBILE --}}
  <div x-show="mobileOpen" x-transition class="fixed inset-0 z-40 md:hidden">
    <div @click="mobileOpen = false" class="absolute inset-0 bg-black/50"></div>

    <div x-show="mobileOpen" x-transition
         class="relative w-72 h-full bg-[#344579] text-white shadow-xl">
      <div class="px-4 py-3 flex items-center justify-between border-b border-white/10">
        <div class="font-extrabold tracking-wide">CV. CAHAYA LOWA</div>
        <button @click="mobileOpen = false" class="p-2 rounded-md hover:bg-white/5">
          <i class="fa-solid fa-xmark"></i>
          <span class="sr-only">Close menu</span>
        </button>
      </div>

      <div class="overflow-y-auto h-[calc(100vh-56px)] px-4 py-4 space-y-6">
        {{-- Mobile items (same order) --}}
        @php $active = $is('dashboard'); @endphp
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}">
          <i class="{{ $active ? 'fa-solid' : 'fa-solid opacity-60' }} fa-house"></i>
          <span class="font-medium">Dashboard</span>
        </a>

        @php $active = $is('penjualan.*'); @endphp
        <a href="{{ route('penjualan.index') }}" class="mt-2 flex items-center gap-3 px-3 py-2 rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}">
          <i class="{{ $active ? 'fa-solid' : 'fa-solid opacity-60' }} fa-cash-register"></i>
          <span class="font-medium">Penjualan</span>
        </a>

        {{-- Pembelian group --}}
        @php $openPembelian = $open(['pembelian.*','pemesanan.*','retur-pembelian.*','tagihan.*']); @endphp
        <div x-data="{ open: {{ $openPembelian ? 'true' : 'false' }} }">
          <button @click="open=!open" class="w-full flex items-center gap-3 px-3 py-2 rounded-md text-white/85 hover:bg-white/5">
            <i class="fa-solid fa-bag-shopping opacity-60"></i>
            <span class="flex-1 text-left font-medium">Pembelian</span>
          </button>
          <div x-show="open" x-transition class="mt-1 pl-6 space-y-1">
            <a href="#" class="block px-3 py-2 rounded-md text-[13px] text-white/80 hover:bg-white/5">Daftar Pemesanan</a>
            <a href="#" class="block px-3 py-2 rounded-md text-[13px] text-white/80 hover:bg-white/5">Daftar Pembelian</a>
            <a href="#" class="block px-3 py-2 rounded-md text-[13px] text-white/80 hover:bg-white/5">Retur Pembelian</a>
            <a href="#" class="block px-3 py-2 rounded-md text-[13px] text-white/80 hover:bg-white/5">Daftar Tagihan</a>
          </div>
        </div>

        {{-- rest mobile... --}}
        <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-md text-white/85 hover:bg-white/5">
          <i class="fa-solid fa-house-chimney-medical opacity-60"></i>
          <span class="font-medium">Supplier</span>
        </a>
        <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-md text-white/85 hover:bg-white/5">
          <i class="fa-solid fa-boxes-stacked opacity-60"></i>
          <span class="font-medium">Item</span>
        </a>
        <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-md text-white/85 hover:bg-white/5">
          <i class="fa-solid fa-warehouse opacity-60"></i>
          <span class="font-medium">Gudang</span>
        </a>
      </div>
    </div>
  </div>

  {{-- DESKTOP SIDEBAR --}}
  <div class="hidden md:flex">
    <nav :class="collapsed ? 'w-20' : 'w-[250px]'" style="background-color: {{ $navBG }};" class="h-screen text-white flex flex-col transition-all duration-200">
      <div class="flex items-center justify-between px-3 h-[56px] border-b border-white/10">
        {{-- Brand area: when collapsed show burger icon; when expanded show full brand --}}
        <div class="flex items-center gap-3">
          <button @click="collapsed = false" x-show="collapsed" x-cloak title="Expand sidebar"
                  class="p-2 rounded-md hover:bg-white/5">
            <i class="fa-solid fa-bars"></i>
          </button>

          <div :class="collapsed ? 'hidden' : 'block' " class="text-white text-center font-extrabold tracking-wide">
            CV. CAHAYA LOWA
          </div>
        </div>

        {{-- controls: collapse toggle (icon-only) --}}
        <div class="flex items-center gap-2">
          <button @click="collapsed = !collapsed" title="Toggle sidebar" class="p-2 rounded-md hover:bg-white/5">
            <i class="fa-solid" x-bind:class="collapsed ? 'fa-chevron-right' : 'fa-chevron-left'"></i>
            <span class="sr-only">Toggle sidebar</span>
          </button>
        </div>
      </div>

      <div class="flex-1 overflow-y-auto px-2 py-4">
        {{-- UTAMA --}}
        <div>
          <div :class="collapsed ? 'hidden' : 'block' " class="text-[11px] font-semibold tracking-wider mb-3 text-blue-100/80">UTAMA</div>

          {{-- Dashboard --}}
          @php $active = $is('dashboard'); @endphp
          <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}">
            <i class="{{ $active ? 'fa-solid' : 'fa-solid opacity-60' }} fa-house"></i>
            <span :class="collapsed ? 'hidden' : 'block'" class="font-medium">Dashboard</span>
          </a>

          {{-- Penjualan --}}
          @php $active = $is('penjualan.*'); @endphp
          <a href="{{ route('penjualan.index') }}" class="mt-2 flex items-center gap-3 px-3 py-2 rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}">
            <i class="{{ $active ? 'fa-solid' : 'fa-solid opacity-60' }} fa-cash-register"></i>
            <span :class="collapsed ? 'hidden' : 'block'" class="font-medium">Penjualan</span>
          </a>

          {{-- Pembelian (no chevron shown on items) --}}
          @php $openPembelian = $open(['pembelian.*','pemesanan.*','retur-pembelian.*','tagihan.*']); @endphp
          <div x-data="{ open: {{ $openPembelian ? 'true' : 'false' }} }" class="mt-2">
            <button @click="open=!open" class="w-full flex items-center gap-3 px-3 py-2 rounded-md text-white/85 hover:bg-white/5">
              <i class="fa-solid fa-bag-shopping opacity-60"></i>
              <span :class="collapsed ? 'hidden' : 'block'" class="flex-1 text-left font-medium">Pembelian</span>
            </button>

            <div x-show="open" x-transition class="mt-1 pl-6 space-y-1">
              <a href="#" class="block px-3 py-2 rounded-md text-[13px] transition text-white/80 hover:bg-white/5">
                <span :class="collapsed ? 'hidden' : 'inline-block'">Daftar Pemesanan</span>
              </a>
              <a href="#" class="block px-3 py-2 rounded-md text-[13px] transition text-white/80 hover:bg-white/5">
                <span :class="collapsed ? 'hidden' : 'inline-block'">Daftar Pembelian</span>
              </a>
              <a href="#" class="block px-3 py-2 rounded-md text-[13px] transition text-white/80 hover:bg-white/5">
                <span :class="collapsed ? 'hidden' : 'inline-block'">Retur Pembelian</span>
              </a>
              <a href="#" class="block px-3 py-2 rounded-md text-[13px] transition text-white/80 hover:bg-white/5">
                <span :class="collapsed ? 'hidden' : 'inline-block'">Daftar Tagihan</span>
              </a>
            </div>
          </div>
        </div>

        {{-- MANAJEMEN TOKO --}}
        <div class="pt-4 border-t border-white/10">
          <div :class="collapsed ? 'hidden' : 'block'" class="text-[11px] font-semibold tracking-wider mb-3 text-blue-100/80">MANAJEMEN TOKO</div>

          {{-- Supplier --}}
          @php $active = $is('supplier.*'); @endphp
          <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}">
            <i class="{{ $active ? 'fa-solid' : 'fa-solid opacity-60' }} fa-house-chimney-medical"></i>
            <span :class="collapsed ? 'hidden' : 'block'" class="font-medium">Supplier</span>
          </a>

          {{-- Item --}}
          @php $openItem = $open(['item.*']); @endphp
          <div x-data="{ open: {{ $openItem ? 'true' : 'false' }} }" class="mt-2">
            <button @click="open=!open" class="w-full flex items-center gap-3 px-3 py-2 rounded-md text-white/85 hover:bg-white/5">
              <i class="fa-solid fa-boxes-stacked opacity-60"></i>
              <span :class="collapsed ? 'hidden' : 'block'" class="flex-1 text-left font-medium">Item</span>
            </button>
            <div x-show="open" x-transition class="mt-1 pl-6 space-y-1">
              <a href="#" class="block px-3 py-2 rounded-md text-[13px] transition text-white/80 hover:bg-white/5">
                <span :class="collapsed ? 'hidden' : 'inline-block'">Daftar Item</span>
              </a>
            </div>
          </div>

          {{-- Gudang --}}
          @php $openGudang = $open(['gudang.*']); @endphp
          <div x-data="{ open: {{ $openGudang ? 'true' : 'false' }} }" class="mt-2">
            <button @click="open=!open" class="w-full flex items-center gap-3 px-3 py-2 rounded-md text-white/85 hover:bg-white/5">
              <i class="fa-solid fa-warehouse opacity-60"></i>
              <span :class="collapsed ? 'hidden' : 'block'" class="flex-1 text-left font-medium">Gudang</span>
            </button>
            <div x-show="open" x-transition class="mt-1 pl-6 space-y-1">
              <a href="#" class="block px-3 py-2 rounded-md text-[13px] transition text-white/80 hover:bg-white/5">
                <span :class="collapsed ? 'hidden' : 'inline-block'">Stok & Lokasi</span>
              </a>
            </div>
          </div>

          {{-- Pelanggan --}}
          @php $openCust = $open(['pelanggan.*']); @endphp
          <div x-data="{ open: {{ $openCust ? 'true' : 'false' }} }" class="mt-2">
            <button @click="open=!open" class="w-full flex items-center gap-3 px-3 py-2 rounded-md text-white/85 hover:bg-white/5">
              <i class="fa-regular fa-user opacity-80"></i>
              <span :class="collapsed ? 'hidden' : 'block'" class="flex-1 text-left font-medium">Pelanggan</span>
            </button>
            <div x-show="open" x-transition class="mt-1 pl-6 space-y-1">
              <a href="#" class="block px-3 py-2 rounded-md text-[13px] transition text-white/80 hover:bg-white/5">
                <span :class="collapsed ? 'hidden' : 'inline-block'">Daftar Pelanggan</span>
              </a>
            </div>
          </div>
        </div>

      </div>

      {{-- footer kecil --}}
      <div class="px-3 py-4 border-t border-white/5">
        <div class="text-[13px] text-white/70" :class="collapsed ? 'hidden' : ''">App version 1.0</div>
      </div>
    </nav>
  </div>
</aside>
