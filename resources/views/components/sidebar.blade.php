{{-- resources/views/components/sidebar.blade.php --}}
@php
  $brand = '#4BAC87';
  $is   = fn($p) => request()->routeIs($p);
  $open = fn($arr) => collect($arr)->contains(fn($p)=> request()->routeIs($p));
@endphp

<aside class="w-[272px] bg-white border-r border-slate-200 h-screen flex flex-col">
  {{-- ===== Profil Akun (TOP) ===== --}}
  <div class="px-5 pt-6 pb-4 border-b border-slate-200">
    <div x-data="{open:false}" class="relative">
      <button @click="open=!open" class="w-full flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50">
        <img
          src="{{ auth()->user()->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name ?? 'User') }}"
          alt="avatar" class="w-9 h-9 rounded-full object-cover">
        <div class="flex-1 text-left">
          <div class="text-sm font-semibold text-slate-700">
            {{ auth()->user()->name ?? 'Nama Pengguna' }}
          </div>
          <div class="text-xs text-slate-500">
            {{ auth()->user()->role ?? 'Administrator' }}
          </div>
        </div>
        <i class="fa-solid fa-ellipsis-vertical text-slate-500"></i>
      </button>

     
    </div>

    {{-- Search (tetap gaya yang sama) --}}
    <div class="mt-5">
      <div class="relative">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
        <input type="text" placeholder="Cari disini..."
               class="w-full pl-10 pr-3 py-2 border border-slate-200 rounded-sm text-md text-slate-600 placeholder-slate-400
                      focus:outline-none focus:ring-2 focus:ring-[{{ $brand }}]/30 focus:border-[{{ $brand }}]">
      </div>
    </div>
  </div>

  {{-- ===== NAV ===== --}}
  <div class="flex-1 overflow-y-auto px-5 pb-4 mt-4 space-y-7">

    {{-- ===== UTAMA ===== --}}
    <div>
      <div class="text-xs font-medium text-slate-400 tracking-wider mb-3">UTAMA</div>

      {{-- Dashboard (aktif pill hijau – style tetap) --}}
      <a href="{{ route('dashboard') }}"
         class="flex items-center gap-3 px-3 py-2.5 rounded-md font-semibold
                {{ $is('dashboard') ? 'text-white' : 'text-slate-700 hover:bg-slate-50' }}"
         style="{{ $is('dashboard') ? "background-color: {$brand};" : '' }}">
        <i class="fa-solid fa-house {{ $is('dashboard') ? 'text-white' : 'text-slate-600' }}"></i>
        <span>Dashboard</span>
      </a>

      {{-- Penjualan --}}
      <a href="{{ route('penjualan.index') }}"
         class="mt-2 flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium
                {{ $is('penjualan.*') ? 'text-['.$brand.'] bg-slate-50' : 'text-slate-700 hover:bg-slate-50' }}">
        <i class="fa-solid fa-cash-register {{ $is('penjualan.*') ? 'text-['.$brand.']' : 'text-slate-600' }}"></i>
        <span>Penjualan</span>
      </a>

      {{-- Pembelian (submenu) --}}
      @php $openPembelian = $open(['pembelian.*','pemesanan.*','retur-pembelian.*','tagihan.*']); @endphp
      <div x-data="{open: {{ $openPembelian ? 'true' : 'false' }}}" class="mt-2">
        <button @click="open=!open"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium
                       {{ $openPembelian ? 'text-slate-700 bg-slate-50' : 'text-slate-700 hover:bg-slate-50' }}">
          <i class="fa-solid fa-bag-shopping text-slate-600"></i>
          <span class="flex-1 text-left">Pembelian</span>
          <i class="fa-solid fa-chevron-down text-slate-500 transition-transform duration-200"
             :class="open ? 'rotate-180' : ''"></i>
        </button>

        <div x-show="open" x-collapse class="mt-1 pl-6 space-y-1">
          <a href=""
             class="block px-3 py-2 rounded-lg text-sm font-medium
                    {{ $is('pemesanan.*') ? 'text-['.$brand.'] bg-slate-50' : 'text-slate-600 hover:bg-slate-50' }}">
            <span class="flex items-center gap-3">
              <span class="inline-block w-[3px] h-5 rounded"
                    style="{{ $is('pemesanan.*') ? "background-color: {$brand};" : 'background-color: transparent;' }}"></span>
              <span>Daftar Pemesanan</span>
            </span>
          </a>

          <a href=""
             class="block px-3 py-2 rounded-lg text-sm font-semibold
                    {{ $is('pembelian.*') ? 'text-['.$brand.'] bg-slate-50' : 'text-slate-600 hover:bg-slate-50' }}">
            <span class="flex items-center gap-3">
              <span class="inline-block w-[3px] h-5 rounded"
                    style="{{ $is('pembelian.*') ? "background-color: {$brand};" : 'background-color: transparent;' }}"></span>
              <span>Daftar Pembelian</span>
            </span>
          </a>

          <a href=""
             class="block px-3 py-2 rounded-lg text-sm font-medium
                    {{ $is('retur-pembelian.*') ? 'text-['.$brand.'] bg-slate-50' : 'text-slate-600 hover:bg-slate-50' }}">
            <span class="flex items-center gap-3">
              <span class="inline-block w-[3px] h-5 rounded"
                    style="{{ $is('retur-pembelian.*') ? "background-color: {$brand};" : 'background-color: transparent;' }}"></span>
              <span>Retur Pembelian</span>
            </span>
          </a>

          <a href=""
             class="block px-3 py-2 rounded-lg text-sm font-medium
                    {{ $is('tagihan.*') ? 'text-['.$brand.'] bg-slate-50' : 'text-slate-600 hover:bg-slate-50' }}">
            <span class="flex items-center gap-3">
              <span class="inline-block w-[3px] h-5 rounded"
                    style="{{ $is('tagihan.*') ? "background-color: {$brand};" : 'background-color: transparent;' }}"></span>
              <span>Daftar Tagihan</span>
            </span>
          </a>
        </div>
      </div>
    </div>

    {{-- ===== MANAJEMEN TOKO ===== --}}
    <div class="pt-4 border-t border-slate-200">
      <div class="text-xs font-semibold text-slate-400 tracking-wider mb-3">MANAJEMEN TOKO</div>

      <a href=""
         class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium
                {{ $is('supplier.*') ? 'text-['.$brand.'] bg-slate-50' : 'text-slate-700 hover:bg-slate-50' }}">
        <i class="fa-solid fa-house-chimney-medical {{ $is('supplier.*') ? 'text-['.$brand.']' : 'text-slate-600' }}"></i>
        <span>Supplier</span>
      </a>

      @php $openItem = $open(['item.*']); @endphp
      <div x-data="{open: {{ $openItem ? 'true' : 'false' }}}" class="mt-2">
        <button @click="open=!open"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium
                       {{ $openItem ? 'text-slate-700 bg-slate-50' : 'text-slate-700 hover:bg-slate-50' }}">
          <i class="fa-solid fa-user-group text-slate-600"></i>
          <span class="flex-1 text-left">Item</span>
          <i class="fa-solid fa-chevron-down text-slate-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
        </button>
        <div x-show="open" x-collapse class="mt-1 pl-6 space-y-1">
          <a href=""
             class="block px-3 py-2 rounded-lg text-sm font-medium
                    {{ $is('item.index') ? 'text-['.$brand.'] bg-slate-50' : 'text-slate-600 hover:bg-slate-50' }}">
            <span class="flex items-center gap-3">
              <span class="inline-block w-[3px] h-5 rounded"
                    style="{{ $is('item.index') ? "background-color: {$brand};" : 'background-color: transparent;' }}"></span>
              <span>Daftar Item</span>
            </span>
          </a>
        </div>
      </div>

      @php $openGudang = $open(['gudang.*']); @endphp
      <div x-data="{open: {{ $openGudang ? 'true' : 'false' }}}" class="mt-2">
        <button @click="open=!open"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium
                       {{ $openGudang ? 'text-slate-700 bg-slate-50' : 'text-slate-700 hover:bg-slate-50' }}">
          <i class="fa-solid fa-warehouse text-slate-800"></i>
          <span class="flex-1 text-left">Gudang</span>
          <i class="fa-solid fa-chevron-down text-slate-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
        </button>
        <div x-show="open" x-collapse class="mt-1 pl-6 space-y-1">
          <a href=""
             class="block px-3 py-2 rounded-lg text-sm font-medium
                    {{ $is('gudang.index') ? 'text-['.$brand.'] bg-slate-50' : 'text-slate-600 hover:bg-slate-50' }}">
            <span class="flex items-center gap-3">
              <span class="inline-block w-[3px] h-5 rounded"
                    style="{{ $is('gudang.index') ? "background-color: {$brand};" : 'background-color: transparent;' }}"></span>
              <span>Stok & Lokasi</span>
            </span>
          </a>
        </div>
      </div>

      @php $openCust = $open(['pelanggan.*']); @endphp
      <div x-data="{open: {{ $openCust ? 'true' : 'false' }}}" class="mt-2">
        <button @click="open=!open"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium
                       {{ $openCust ? 'text-slate-700 bg-slate-50' : 'text-slate-700 hover:bg-slate-50' }}">
          <i class="fa-solid fa-user text-slate-700"></i>
          <span class="flex-1 text-left">Pelanggan</span>
          <i class="fa-solid fa-chevron-down text-slate-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
        </button>
        <div x-show="open" x-collapse class="mt-1 pl-6 space-y-1">
          <a href=""
             class="block px-3 py-2 rounded-lg text-sm font-medium
                    {{ $is('pelanggan.index') ? 'text-['.$brand.'] bg-slate-50' : 'text-slate-600 hover:bg-slate-50' }}">
            <span class="flex items-center gap-3">
              <span class="inline-block w-[3px] h-5 rounded"
                    style="{{ $is('pelanggan.index') ? "background-color: {$brand};" : 'background-color: transparent;' }}"></span>
              <span>Daftar Pelanggan</span>
            </span>
          </a>
        </div>
      </div>
    </div>

    {{-- ===== MANAJEMEN PENGGUNA ===== --}}
    <div class="pt-4 border-t border-slate-200">
      <div class="text-xs font-semibold text-slate-400 tracking-wider mb-3">MANAJEMEN PENGGUNA</div>

      @php $openUser = $open(['user.*','candidate.*','personalia.*']); @endphp
      <div x-data="{open: {{ $openUser ? 'true' : 'false' }}}">
        <button @click="open=!open"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold
                       {{ $openUser ? 'bg-slate-100 text-slate-700' : 'text-slate-700 hover:bg-slate-50' }}">
          <i class="fa-solid fa-user-plus text-slate-700"></i>
          <span class="flex-1 text-left">Manajemen Pengguna</span>
          <i class="fa-solid fa-chevron-down text-slate-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
        </button>

        <div x-show="open" x-collapse class="mt-1 pl-6 space-y-1">
          <a href=""
             class="block px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50">
            <span class="flex items-center gap-3">
              <span class="inline-block w-[3px] h-5 rounded bg-transparent"></span>
              <span>Candidate Requirement</span>
            </span>
          </a>

          <a href=""
             class="block px-3 py-2 rounded-lg text-sm font-semibold
                    {{ $is('candidate.*') ? 'text-['.$brand.'] bg-slate-50' : 'text-slate-600 hover:bg-slate-50' }}">
            <span class="flex items-center gap-3">
              <span class="inline-block w-[3px] h-5 rounded"
                    style="{{ $is('candidate.*') ? "background-color: {$brand};" : 'background-color: transparent;' }}"></span>
              <span>Candidate</span>
            </span>
          </a>

          <a href=""
             class="block px-3 py-2 rounded-lg text-sm font-medium
                    {{ $is('personalia.*') ? 'text-['.$brand.'] bg-slate-50' : 'text-slate-600 hover:bg-slate-50' }}">
            <span class="flex items-center gap-3">
              <span class="inline-block w-[3px] h-5 rounded"
                    style="{{ $is('personalia.*') ? "background-color: {$brand};" : 'background-color: transparent;' }}"></span>
              <span>Personalia</span>
            </span>
          </a>
        </div>
      </div>
    </div>
  </div>
</aside>
