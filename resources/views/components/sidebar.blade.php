{{-- resources/views/components/sidebar.blade.php --}}
@php
    $brand = '#4BAC87';
    $navBG = '#344579';
    $is = fn($p) => request()->routeIs($p);
    $open = fn($ar) => collect($ar)->contains(fn($p) => request()->routeIs($p));
@endphp

<aside x-data="{
    collapsed: false,
    init() {
        try { this.collapsed = localStorage.getItem('sidebar-collapsed') === '1' } catch (e) {}
        this.$dispatch('sidebar-toggled', { collapsed: this.collapsed });
    },
    toggle() {
        this.collapsed = !this.collapsed;
        try { localStorage.setItem('sidebar-collapsed', this.collapsed ? '1' : '0') } catch (e) {}
        this.$dispatch('sidebar-toggled', { collapsed: this.collapsed });
    }
}" x-init="init()" :class="collapsed ? 'w-[64px]' : 'w-[250px]'"
    class="bg-[#344579] text-white flex flex-col transition-all duration-300 h-screen fixed left-0 top-0 z-40"
    style="background-color: {{ $navBG }}">

    {{-- HEADER LOGO & TOGGLER - FIXED --}}
    <div class="h-[56px] flex items-center border-b border-white/10 px-3 flex-shrink-0">
        <button @click="toggle()"
            class="flex items-center justify-center text-white hover:text-gray-200 focus:outline-none w-[40px] h-[40px]">
            <i class="fa-solid" :class="collapsed ? 'fa-bars' : 'fa-xmark'"></i>
        </button>

        <div x-show="!collapsed" class="ml-3 text-white font-extrabold tracking-wide">
            CV. CAHAYA LOWA
        </div>
    </div>

    {{-- NAV - SCROLLABLE (HIDDEN SCROLLBAR) --}}
    <div class="flex-1 overflow-y-auto px-4 pb-4 mt-4 space-y-7 scrollbar-hide"
        style="scrollbar-width: none; -ms-overflow-style: none;">

        {{-- ==================== --}}
        {{-- SECTION: UTAMA --}}
        {{-- ==================== --}}
        @php
            // Cek apakah ada menu di section UTAMA
            $hasDashboard = auth()->user()->can('dashboard.view');
            $canViewCekHarga = auth()->user()->can('cek_harga.view');
            $canViewPenjualan = auth()->user()->can('penjualan.view');
            $canViewPengiriman = auth()->user()->can('pengiriman.view');
            $canViewRetur = auth()->user()->can('retur_penjualan.view');
            $canViewPenjualanCepat = auth()->user()->can('penjualan_cepat.view');
            $canViewPembayaran = auth()->user()->can('pembayaran.view');
            $canViewTagihanPenjualan = auth()->user()->can('tagihan_penjualan.view');
            $canViewPembelian = auth()->user()->can('pembelian.view');
            $canViewReturPembelian = auth()->user()->can('retur_pembelian.view');
            $canViewTagihanPembelian = auth()->user()->can('tagihan_pembelian.view');

            $showPenjualanSection = $canViewPenjualan || $canViewPengiriman || $canViewRetur;
            $showKasirSection = $canViewPenjualanCepat || $canViewPembayaran || $canViewTagihanPenjualan;
            $showPembelianSection = $canViewPembelian || $canViewReturPembelian || $canViewTagihanPembelian;

            // Show section UTAMA jika ada minimal 1 menu
            $showUtamaSection =
                $hasDashboard ||
                $canViewCekHarga ||
                $showPenjualanSection ||
                $showKasirSection ||
                $showPembelianSection;
        @endphp


        @if ($showUtamaSection)
            <div>
                <div x-show="!collapsed" class="text-[11px] font-semibold tracking-wider mb-3 text-blue-100/80">UTAMA
                </div>

                {{-- Dashboard --}}
                @can('dashboard.view')
                    @php $active = $is('dashboard'); @endphp
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center gap-3 px-3 py-[10px] rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}"
                        :class="{ 'justify-center': collapsed }">
                        <i class="fa-solid fa-house" :class="collapsed ? 'text-lg' : ''"></i>
                        <span x-show="!collapsed" class="font-medium">Dashboard</span>
                    </a>
                @endcan

                {{-- Cek Harga --}}
                @can('cek_harga.view')
                    @php $active = $is('cek-harga.*'); @endphp
                    <a href="{{ route('cek-harga.index') }}"
                        class="mt-2 flex items-center gap-3 px-3 py-[10px] rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}"
                        :class="{ 'justify-center': collapsed }">
                        <i class="fa-solid fa-barcode" :class="collapsed ? 'text-lg' : ''"></i>
                        <span x-show="!collapsed" class="font-medium">Cek Harga</span>
                    </a>
                @endcan

                {{-- ==================== --}}
                {{-- PENJUALAN (Submenu) --}}
                {{-- ==================== --}}
                @if ($showPenjualanSection)
                    @php $openPenjualan = $open(['penjualan.*', 'retur-penjualan.*', 'pengiriman.*']); @endphp
                    <div x-data="{ open: {{ $openPenjualan ? 'true' : 'false' }} }" class="mt-2">
                        <button @click="open=!open"
                            class="w-full flex items-center gap-3 px-3 py-[10px] rounded-md transition text-white/85 hover:bg-white/5"
                            :class="{ 'justify-center': collapsed }">
                            <i class="fa-solid fa-cash-register" :class="collapsed ? 'text-lg' : ''"></i>
                            <span x-show="!collapsed" class="flex-1 text-left font-medium">Penjualan</span>
                            <i x-show="!collapsed"
                                class="fa-solid fa-chevron-down text-white/60 transition-transform duration-200"
                                :class="open ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="open && !collapsed" x-transition class="mt-1 pl-6 space-y-1">
                            {{-- Daftar Penjualan --}}
                            @can('penjualan.view')
                                @php $on = $is('penjualan.*'); @endphp
                                <a href="{{ route('penjualan.index') }}"
                                    class="block px-3 py-2 rounded-md text-[13px] transition {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                                    <span class="flex items-center gap-3">
                                        <span
                                            class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                                        <span>Daftar Penjualan</span>
                                    </span>
                                </a>
                            @endcan

                            {{-- Daftar Pengiriman --}}
                            @can('pengiriman.view')
                                @php $on = $is('pengiriman.*'); @endphp
                                <a href="{{ route('pengiriman.index') }}"
                                    class="block px-3 py-2 rounded-md text-[13px] transition {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                                    <span class="flex items-center gap-3">
                                        <span
                                            class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                                        <span>Daftar Pengiriman</span>
                                    </span>
                                </a>
                            @endcan

                            {{-- Retur Penjualan --}}
                            @can('retur_penjualan.view')
                                @php $on = $is('retur-penjualan.*'); @endphp
                                <a href="{{ route('retur-penjualan.index') }}"
                                    class="block px-3 py-2 rounded-md text-[13px] transition {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                                    <span class="flex items-center gap-3">
                                        <span
                                            class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                                        <span>Retur Penjualan</span>
                                    </span>
                                </a>
                            @endcan
                        </div>
                    </div>
                @endif

                {{-- ==================== --}}
                {{-- KASIR (Submenu) --}}
                {{-- ==================== --}}
                @if ($showKasirSection)
                    @php $openKasir = $open(['penjualan-cepat.*', 'pembayaran.*', 'tagihan-penjualan.*']); @endphp
                    <div x-data="{ open: {{ $openKasir ? 'true' : 'false' }} }" class="mt-2">
                        <button @click="open=!open"
                            class="w-full flex items-center gap-3 px-3 py-[10px] rounded-md transition text-white/85 hover:bg-white/5"
                            :class="{ 'justify-center': collapsed }">
                            <i class="fa-solid fa-cart-shopping" :class="collapsed ? 'text-lg' : ''"></i>
                            <span x-show="!collapsed" class="flex-1 text-left font-medium">Kasir</span>
                            <i x-show="!collapsed"
                                class="fa-solid fa-chevron-down text-white/60 transition-transform duration-200"
                                :class="open ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="open && !collapsed" x-transition class="mt-1 pl-6 space-y-1">
                            {{-- Penjualan Cepat --}}
                            @can('penjualan_cepat.view')
                                @php $on = $is('penjualan-cepat.*'); @endphp
                                <a href="{{ route('penjualan-cepat.index') }}"
                                    class="block px-3 py-2 rounded-md text-[13px] transition {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                                    <span class="flex items-center gap-3">
                                        <span
                                            class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                                        <span>Penjualan Cepat</span>
                                    </span>
                                </a>
                            @endcan

                            {{-- Pembayaran --}}
                            @can('pembayaran.view')
                                @php $on = $is('pembayaran.*'); @endphp
                                <a href="{{ route('pembayaran.index') }}"
                                    class="block px-3 py-2 rounded-md text-[13px] transition {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                                    <span class="flex items-center gap-3">
                                        <span
                                            class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                                        <span>Pembayaran</span>
                                    </span>
                                </a>
                            @endcan

                            {{-- Tagihan Penjualan --}}
                            @can('tagihan_penjualan.view')
                                @php $on = $is('tagihan-penjualan.*'); @endphp
                                <a href="{{ route('tagihan-penjualan.index') }}"
                                    class="block px-3 py-2 rounded-md text-[13px] transition {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                                    <span class="flex items-center gap-3">
                                        <span
                                            class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                                        <span>Tagihan Penjualan</span>
                                    </span>
                                </a>
                            @endcan
                        </div>
                    </div>
                @endif

                {{-- ==================== --}}
                {{-- PEMBELIAN (Submenu) --}}
                {{-- ==================== --}}
                @if ($showPembelianSection)
                    @php $openPembelian = $open(['pembelian.*', 'retur-pembelian.*', 'tagihan-pembelian.*']); @endphp
                    <div x-data="{ open: {{ $openPembelian ? 'true' : 'false' }} }" class="mt-2">
                        <button @click="open = !open"
                            class="w-full flex items-center gap-3 px-3 py-[10px] rounded-md transition text-white/85 hover:bg-white/5"
                            :class="{ 'justify-center': collapsed }">
                            <i class="fa-solid fa-bag-shopping" :class="collapsed ? 'text-lg' : ''"></i>
                            <span x-show="!collapsed" class="flex-1 text-left font-medium">Pembelian</span>
                            <i x-show="!collapsed"
                                class="fa-solid fa-chevron-down text-white/60 transition-transform duration-200"
                                :class="open ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="open && !collapsed" x-transition class="mt-1 pl-6 space-y-1">
                            {{-- Daftar Pembelian --}}
                            @can('pembelian.view')
                                @php $on = $is('pembelian.index'); @endphp
                                <a href="{{ route('pembelian.index') }}"
                                    class="block px-3 py-2 rounded-md text-[13px] transition {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                                    <span class="flex items-center gap-3">
                                        <span
                                            class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                                        <span>Daftar Pembelian</span>
                                    </span>
                                </a>
                            @endcan

                            {{-- Retur Pembelian --}}
                            @can('retur_pembelian.view')
                                @php $on = $is('retur-pembelian.*'); @endphp
                                <a href="{{ route('retur-pembelian.index') }}"
                                    class="block px-3 py-2 rounded-md text-[13px] transition {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                                    <span class="flex items-center gap-3">
                                        <span
                                            class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                                        <span>Retur Pembelian</span>
                                    </span>
                                </a>
                            @endcan

                            {{-- Tagihan Pembelian --}}
                            @can('tagihan_pembelian.view')
                                @php $on = $is('tagihan-pembelian.*'); @endphp
                                <a href="{{ route('tagihan-pembelian.index') }}"
                                    class="block px-3 py-2 rounded-md text-[13px] transition {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                                    <span class="flex items-center gap-3">
                                        <span
                                            class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                                        <span>Tagihan Pembelian</span>
                                    </span>
                                </a>
                            @endcan
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- ==================== --}}
        {{-- SECTION: MANAJEMEN TOKO --}}
        {{-- ==================== --}}
        @php
            $canViewGudang = auth()->user()->can('gudang.view');
            $canViewSupplier = auth()->user()->can('supplier.view');
            $canViewItems = auth()->user()->can('items.view');
            $canViewKategori = auth()->user()->can('kategori_items.view');
            $canViewPelanggan = auth()->user()->can('pelanggan.view');
            $canViewMutasi = auth()->user()->can('mutasi_stok.view');
            $canViewProduksi = auth()->user()->can('produksi.view');

            $showManajemenToko =
                $canViewGudang ||
                $canViewSupplier ||
                $canViewItems ||
                $canViewKategori ||
                $canViewPelanggan ||
                $canViewMutasi ||
                $canViewProduksi;
        @endphp

        @if ($showManajemenToko)
            <div class="pt-4  border-t border-white/10">
                <div x-show="!collapsed" class="text-[11px] font-semibold tracking-wider mb-3 text-blue-100/80">
                    MANAJEMEN TOKO
                </div>

                {{-- Gudang --}}
                @can('gudang.view')
                    @php $active = $is('gudang.*'); @endphp
                    <a href="{{ route('gudang.index') }}"
                        class="flex items-center gap-3 px-3 py-[10px] rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}"
                        :class="{ 'justify-center': collapsed }">
                        <i class="fa-solid fa-warehouse" :class="collapsed ? 'text-lg' : ''"></i>
                        <span x-show="!collapsed" class="font-medium">Gudang</span>
                    </a>
                @endcan

                {{-- Supplier --}}
                @can('supplier.view')
                    @php $active = $is('supplier.*'); @endphp
                    <a href="{{ route('supplier.index') }}"
                        class="mt-2 flex items-center gap-3 px-3 py-[10px] rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}"
                        :class="{ 'justify-center': collapsed }">
                        <i class="fa-solid fa-handshake-angle" :class="collapsed ? 'text-lg' : ''"></i>
                        <span x-show="!collapsed" class="font-medium">Supplier</span>
                    </a>
                @endcan

                {{-- Item (Submenu) --}}
                @if ($canViewItems || $canViewKategori)
                    @php $openItem = $open(['items.*', 'items.categories.*']); @endphp
                    <div x-data="{ open: {{ $openItem ? 'true' : 'false' }} }" class="mt-2">
                        <button @click="open=!open"
                            class="w-full flex items-center gap-3 px-3 py-[10px] rounded-md transition text-white/85 hover:bg-white/5"
                            :class="{ 'justify-center': collapsed }">
                            <i class="fa-solid fa-boxes" :class="collapsed ? 'text-lg' : ''"></i>
                            <span x-show="!collapsed" class="flex-1 text-left font-medium">Item</span>
                            <i x-show="!collapsed"
                                class="fa-solid fa-chevron-down text-white/60 transition-transform duration-200"
                                :class="open ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="open && !collapsed" x-transition class="mt-1 pl-6 space-y-1">
                            @can('kategori_items.view')
                                @php $on = $is('items.categories.*'); @endphp
                                <a href="{{ route('items.categories.index') }}"
                                    class="block px-3 py-2 rounded-md text-[13px] transition {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                                    <span class="flex items-center gap-3">
                                        <span
                                            class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                                        <span>Kategori Item</span>
                                    </span>
                                </a>
                            @endcan

                            @can('items.view')
                                @php $on = $is('items.index') || ($is('items.*') && !$is('items.categories.*')); @endphp
                                <a href="{{ route('items.index') }}"
                                    class="block px-3 py-2 rounded-md text-[13px] transition {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                                    <span class="flex items-center gap-3">
                                        <span
                                            class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                                        <span>Daftar Item</span>
                                    </span>
                                </a>
                            @endcan
                        </div>
                    </div>
                @endif

                {{-- Pelanggan --}}
                @can('pelanggan.view')
                    @php $active = $is('pelanggan.*'); @endphp
                    <a href="{{ route('pelanggan.index') }}"
                        class="mt-2 flex items-center gap-3 px-3 py-[10px] rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}"
                        :class="{ 'justify-center': collapsed }">
                        <i class="fa-solid fa-users" :class="collapsed ? 'text-lg' : ''"></i>
                        <span x-show="!collapsed" class="font-medium">Pelanggan</span>
                    </a>
                @endcan

                {{-- Mutasi Stok --}}
                @can('mutasi_stok.view')
                    @php $active = $is('mutasi-stok.*'); @endphp
                    <a href="{{ route('mutasi-stok.index') }}"
                        class="mt-2 flex items-center gap-3 px-3 py-[10px] rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}"
                        :class="{ 'justify-center': collapsed }">
                        <i class="fa-solid fa-right-left" :class="collapsed ? 'text-lg' : ''"></i>
                        <span x-show="!collapsed" class="font-medium">Mutasi Stok</span>
                    </a>
                @endcan

                {{-- Produksi --}}
                @can('produksi.view')
                    @php $active = $is('produksi.*'); @endphp
                    <a href="{{ route('produksi.index') }}"
                        class="mt-2 flex items-center gap-3 px-3 py-[10px] rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}"
                        :class="{ 'justify-center': collapsed }">
                        <i class="fa-solid fa-hammer" :class="collapsed ? 'text-lg' : ''"></i>
                        <span x-show="!collapsed" class="font-medium">Produksi</span>
                    </a>
                @endcan
            </div>
        @endif

        {{-- ==================== --}}
        {{-- SECTION: MANAJEMEN PENGGUNA --}}
        {{-- ==================== --}}
        @php
            $canViewRoles = auth()->user()->can('roles.view');
            $canViewUsers = auth()->user()->can('users.view');
            $canViewActivityLogs = auth()->user()->can('activity_logs.view');
            $showManajemenPengguna = $canViewRoles || $canViewUsers || $canViewActivityLogs;
        @endphp

        @if ($showManajemenPengguna)
            <div class="pt-4 border-t border-white/10">
                <div x-show="!collapsed" class="text-[11px] font-semibold tracking-wider mb-3 text-blue-100/80">
                    MANAJEMEN PENGGUNA
                </div>

                {{-- Role --}}
                @can('roles.view')
                    @php $active = $is('roles.*'); @endphp
                    <a href="{{ route('roles.index') }}"
                        class="flex items-center gap-3 px-3 py-[10px] rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}"
                        :class="{ 'justify-center': collapsed }">
                        <i class="fa-solid fa-user-shield" :class="collapsed ? 'text-lg' : ''"></i>
                        <span x-show="!collapsed" class="font-medium">Role</span>
                    </a>
                @endcan

                {{-- Daftar Akun --}}
                @can('users.view')
                    @php $active = $is('users.*') || $is('akun.*'); @endphp
                    <a href="{{ route('users.index') }}"
                        class="mt-2 flex items-center gap-3 px-3 py-[10px] rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}"
                        :class="{ 'justify-center': collapsed }">
                        <i class="fa-solid fa-users-gear" :class="collapsed ? 'text-lg' : ''"></i>
                        <span x-show="!collapsed" class="font-medium">Daftar Akun</span>
                    </a>
                @endcan

                {{-- Aktivitas Pengguna --}}
                @can('activity_logs.view')
                    @php $active = $is('log-activity.*'); @endphp
                    <a href="{{ route('log-activity.index') }}"
                        class="mt-2 flex items-center gap-3 px-3 py-[10px] rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}"
                        :class="{ 'justify-center': collapsed }">
                        <i class="fa-solid fa-history" :class="collapsed ? 'text-lg' : ''"></i>
                        <span x-show="!collapsed" class="font-medium">Aktivitas Pengguna</span>
                    </a>
                @endcan
            </div>
        @endif

        {{-- ==================== --}}
        {{-- SECTION: KEUANGAN --}}
        {{-- ==================== --}}
        @php
            $canViewKas = auth()->user()->can('cashflows.view');
            $canViewGaji = auth()->user()->can('payrolls.view');
            $showKeuangan = $canViewKas || $canViewGaji;
        @endphp

        @if ($showKeuangan)
            <div class="pt-4 border-t border-white/10">
                <div x-show="!collapsed" class="text-[11px] font-semibold tracking-wider mb-3 text-blue-100/80">
                    KEUANGAN
                </div>

                {{-- Kas Keuangan --}}
                @can('cashflows.view')
                    @php $active = $is('kas-keuangan.*'); @endphp
                    <a href="{{ route('kas-keuangan.index') }}"
                        class="flex items-center gap-3 px-3 py-[10px] rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}"
                        :class="{ 'justify-center': collapsed }">
                        <i class="fa-solid fa-wallet" :class="collapsed ? 'text-lg' : ''"></i>
                        <span x-show="!collapsed" class="font-medium">Kas Keuangan</span>
                    </a>
                @endcan

                {{-- Gaji Karyawan --}}
                @can('payrolls.view')
                    @php $active = $is('gaji-karyawan.*'); @endphp
                    <a href="{{ route('gaji-karyawan.index') }}"
                        class="mt-2 flex items-center gap-3 px-3 py-[10px] rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}"
                        :class="{ 'justify-center': collapsed }">
                        <i class="fa-solid fa-file-invoice-dollar" :class="collapsed ? 'text-lg' : ''"></i>
                        <span x-show="!collapsed" class="font-medium">Gaji Karyawan</span>
                    </a>
                @endcan
            </div>
        @endif

    </div>

    {{-- Custom CSS untuk menyembunyikan scrollbar --}}
    <style>
        /* Hide scrollbar for Chrome, Safari and Opera */
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        /* Hide scrollbar for IE, Edge and Firefox */
        .scrollbar-hide {
            -ms-overflow-style: none;
            /* IE and Edge */
            scrollbar-width: none;
            /* Firefox */
        }
    </style>
</aside>
