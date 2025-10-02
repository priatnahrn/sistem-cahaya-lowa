{{-- resources/views/components/sidebar.blade.php --}}
@php
    $brand = '#4BAC87'; // aksen (dipakai di beberapa place bila perlu)
    $navBG = '#344579'; // warna sidebar utama
    $is = fn($p) => request()->routeIs($p);
    $open = fn($ar) => collect($ar)->contains(fn($p) => request()->routeIs($p));
@endphp

<aside x-data="{
    collapsed: false,
    init() {
        try { this.collapsed = localStorage.getItem('sidebar-collapsed') === '1' } catch (e) {}
    },
    toggle() {
        this.collapsed = !this.collapsed;
        try { localStorage.setItem('sidebar-collapsed', this.collapsed ? '1' : '0') } catch (e) {}
    }
}" x-init="init()" :class="collapsed ? 'w-[64px]' : 'w-[250px]'"
    class="bg-[#344579] text-white flex flex-col transition-all duration-300"
    style="background-color: {{ $navBG }}">

    {{-- HEADER LOGO & TOGGLER --}}
    <div class="h-[56px] flex items-center border-b border-white/10 px-3">
        {{-- Toggle --}}
        <button @click="toggle()"
            class="flex items-center justify-center text-white hover:text-gray-200 focus:outline-none w-[40px] h-[40px]">
            <i class="fa-solid" :class="collapsed ? 'fa-bars' : 'fa-xmark'"></i>
        </button>

        {{-- Brand (only shown when not collapsed) --}}
        <div x-show="!collapsed" class="ml-3 text-white font-extrabold tracking-wide">
            CV. CAHAYA LOWA
        </div>
    </div>

    {{-- NAV --}}
    <div class="flex-1 overflow-y-auto px-4 pb-4 mt-4 space-y-7">

        {{-- UTAMA --}}
        <div>
            <div x-show="!collapsed" class="text-[11px] font-semibold tracking-wider mb-3 text-blue-100/80">UTAMA</div>

            {{-- Dashboard --}}
            @php $active = $is('dashboard'); @endphp
            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-3 px-3 py-[10px] rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}"
                :class="{ 'justify-center': collapsed }">
                <i class="fa-solid fa-house" :class="collapsed ? 'text-lg' : ''"></i>
                <span x-show="!collapsed" class="font-medium">Dashboard</span>
            </a>


            {{-- Penjualan (submenu) --}}
            @php $openPenjualan = $open(['penjualan.*', 'retur-penjualan.*', 'kasir.*']); @endphp
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
                    @php $on = $is('penjualan.*'); @endphp
                    <a href="{{ route('penjualan.index') }}"
                        class="block px-3 py-2 rounded-md text-[13px] transition {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                        <span class="flex items-center gap-3">
                            <span
                                class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                            <span>Daftar Penjualan</span>
                        </span>
                    </a>

                    {{-- Daftar Pengiriman --}}
                    @php $on = $is('daftar-pengiriman.*'); @endphp
                    <a href="{{ route('pengiriman.index') }}"
                        class="block px-3 py-2 rounded-md text-[13px] transition {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                        <span class="flex items-center gap-3">
                            <span
                                class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                            <span>Daftar Pengiriman</span>
                        </span>
                    </a>

                    {{-- Retur Penjualan --}}
                    @php $on = $is('retur-penjualan.*'); @endphp
                    <a href=""
                        class="block px-3 py-2 rounded-md text-[13px] transition {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                        <span class="flex items-center gap-3">
                            <span
                                class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                            <span>Retur Penjualan</span>
                        </span>
                    </a>

                    {{-- Kasir --}}
                    @php $on = $is('kasir.*'); @endphp
                    <a href=""
                        class="block px-3 py-2 rounded-md text-[13px] transition {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                        <span class="flex items-center gap-3">
                            <span
                                class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                            <span>Kasir</span>
                        </span>
                    </a>
                </div>
            </div>


            @php
                $openPembelian = $open([
                    'pembelian.*',
                    'pemesanan.*',
                    'pembelian.retur-pembelian.*',
                    'pembelian.tagihan.*',
                ]);
            @endphp

            <div x-data="{ open: {{ $openPembelian ? 'true' : 'false' }} }" class="mt-2 flex flex-col">

                {{-- Menu Utama Pembelian --}}
                <button @click="open = true; window.location.href='{{ route('pembelian.index') }}';"
                    class="w-full flex items-center gap-3 px-3 py-[10px] rounded-md transition text-white/85 hover:bg-white/5"
                    :class="{ 'justify-center': collapsed }">
                    <i class="fa-solid fa-bag-shopping opacity-60" :class="collapsed ? 'text-lg' : ''"></i>
                    <span x-show="!collapsed" class="flex-1 text-left font-medium">Pembelian</span>
                    <i x-show="!collapsed"
                        class="fa-solid fa-chevron-down text-white/60 transition-transform duration-200"
                        :class="open ? 'rotate-180' : ''"></i>
                </button>

                {{-- Submenu --}}
                <div x-show="open && !collapsed" x-transition class="mt-1 pl-6 space-y-1">

                    {{-- Pemesanan --}}
                    <a href=""
                        class="block px-3 py-2 rounded-md text-[13px] transition {{ $is('pemesanan.*') ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                        <span class="flex items-center gap-3">
                            <span
                                class="inline-block w-[3px] h-5 rounded {{ $is('pemesanan.*') ? 'bg-white' : 'bg-transparent' }}"></span>
                            <span>Daftar Pemesanan</span>
                        </span>
                    </a>

                    {{-- Daftar Pembelian --}}
                    <a href="{{ route('pembelian.index') }}"
                        class="block px-3 py-2 rounded-md text-[13px] transition {{ $is('pembelian.index') ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                        <span class="flex items-center gap-3">
                            <span
                                class="inline-block w-[3px] h-5 rounded {{ $is('pembelian.index') ? 'bg-white' : 'bg-transparent' }}"></span>
                            <span>Daftar Pembelian</span>
                        </span>
                    </a>

                    {{-- Retur Pembelian --}}
                    <a href="{{ route('retur-pembelian.index') }}"
                        class="block px-3 py-2 rounded-md text-[13px] transition {{ $is('pembelian.retur-pembelian.*') ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                        <span class="flex items-center gap-3">
                            <span
                                class="inline-block w-[3px] h-5 rounded {{ $is('pembelian.retur-pembelian.*') ? 'bg-white' : 'bg-transparent' }}"></span>
                            <span>Retur Pembelian</span>
                        </span>
                    </a>

                    {{-- Tagihan Pembelian --}}
                    <a href="{{ route('tagihan.pembelian.index') }}"
                        class="block px-3 py-2 rounded-md text-[13px] transition {{ $is('pembelian.tagihan.*') ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                        <span class="flex items-center gap-3">
                            <span
                                class="inline-block w-[3px] h-5 rounded {{ $is('pembelian.tagihan.*') ? 'bg-white' : 'bg-transparent' }}"></span>
                            <span>Daftar Tagihan</span>
                        </span>
                    </a>
                </div>
            </div>


            {{-- MANAJEMEN TOKO --}}
            <div class="pt-4 border-t border-white/10">
                <div x-show="!collapsed" class="text-[11px] font-semibold tracking-wider mb-3 text-blue-100/80">
                    MANAJEMEN
                    TOKO</div>

                {{-- Gudang --}}
                @php $active = $is('gudang.*'); @endphp
                <a href="{{ route('gudang.index') }}"
                    class="flex items-center gap-3 px-3 py-[10px] rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}"
                    :class="{ 'justify-center': collapsed }">
                    <i class="fa-solid fa-warehouse" :class="collapsed ? 'text-lg' : ''"></i>
                    <span x-show="!collapsed" class="font-medium">Gudang</span>
                </a>

                {{-- Supplier --}}
                @php $active = $is('supplier.*'); @endphp
                <a href="{{ route('supplier.index') }}"
                    class="mt-2 flex items-center gap-3 px-3 py-[10px] rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}"
                    :class="{ 'justify-center': collapsed }">
                    <i class="fa-solid fa-handshake-angle" :class="collapsed ? 'text-lg' : ''"></i>
                    <span x-show="!collapsed" class="font-medium">Supplier</span>
                </a>

                {{-- Item (submenu: Kategori Item + Daftar Item) --}}
                @php
                    // gunakan pola route name yang konsisten: items.* & items.categories.*
                    $openItem = $open(['items.*', 'items.categories.*']);
                @endphp
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
                        {{-- Kategori Item: active when items.categories.* --}}
                        @php $onKategori = $is('items.categories.*'); @endphp
                        <a href="{{ route('items.categories.index') }}"
                            class="block px-3 py-2 rounded-md text-[13px] transition {{ $onKategori ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                            <span class="flex items-center gap-3">
                                <span
                                    class="inline-block w-[3px] h-5 rounded {{ $onKategori ? 'bg-white' : 'bg-transparent' }}"></span>
                                <span>Kategori Item</span>
                            </span>
                        </a>

                        {{-- Daftar Item: active for items.index or other items.* except categories --}}
                        @php $onDaftar = $is('items.index') || ($is('items.*') && ! $is('items.categories.*')); @endphp
                        <a href="{{ route('items.index') }}"
                            class="block px-3 py-2 rounded-md text-[13px] transition {{ $onDaftar ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                            <span class="flex items-center gap-3">
                                <span
                                    class="inline-block w-[3px] h-5 rounded {{ $onDaftar ? 'bg-white' : 'bg-transparent' }}"></span>
                                <span>Daftar Item</span>
                            </span>
                        </a>
                    </div>
                </div>

                {{-- Pelanggan --}}
                @php $active = $is('pelanggan.*'); @endphp
                <a href="{{ route('pelanggan.index') }}"
                    class="mt-2 flex items-center gap-3 px-3 py-[10px] rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}"
                    :class="{ 'justify-center': collapsed }">
                    <i class="fa-solid fa-users" :class="collapsed ? 'text-lg' : ''"></i>
                    <span x-show="!collapsed" class="font-medium">Pelanggan</span>
                </a>
                {{-- Mutasi Stok --}}
                @php $active = $is('mutasi-stok.*'); @endphp
                <a href=""
                    class="mt-2 flex items-center gap-3 px-3 py-[10px] rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}"
                    :class="{ 'justify-center': collapsed }">
                    <i class="fa-solid fa-right-left" :class="collapsed ? 'text-lg' : ''"></i>
                    <span x-show="!collapsed" class="font-medium">Mutasi Stok</span>
                </a>

            </div>

            {{-- MANAJEMEN PENGGUNA --}}
            <div class="pt-4 border-t border-white/10">
                <div x-show="!collapsed" class="text-[11px] font-semibold tracking-wider mb-3 text-blue-100/80">
                    MANAJEMEN
                    PENGGUNA</div>

                {{-- Role --}}
                @php $active = $is('roles.*'); @endphp
                <a href="javascript:void(0)"
                    class="flex items-center gap-3 px-3 py-[10px] rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}"
                    :class="{ 'justify-center': collapsed }">
                    <i class="fa-solid fa-user-shield" :class="collapsed ? 'text-lg' : ''"></i>
                    <span x-show="!collapsed" class="font-medium">Role</span>
                </a>

                {{-- Daftar Akun --}}
                @php $active = $is('users.*') || $is('akun.*'); @endphp
                <a href="javascript:void(0)"
                    class="mt-2 flex items-center gap-3 px-3 py-[10px] rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}"
                    :class="{ 'justify-center': collapsed }">
                    <i class="fa-solid fa-users-gear" :class="collapsed ? 'text-lg' : ''"></i>
                    <span x-show="!collapsed" class="font-medium">Daftar Akun</span>
                </a>

                {{-- Log Aktivitas --}}
                @php $active = $is('log.*'); @endphp
                <a href=""
                    class="mt-2 flex items-center gap-3 px-3 py-[10px] rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}"
                    :class="{ 'justify-center': collapsed }">
                    <i class="fa-solid fa-list-check" :class="collapsed ? 'text-lg' : ''"></i>
                    <span x-show="!collapsed" class="font-medium">Aktivitas Pengguna</span>
                </a>

            </div>

            {{-- KEUANGAN --}}
            <div class="pt-4 border-t border-white/10">
                <div x-show="!collapsed" class="text-[11px] font-semibold tracking-wider mb-3 text-blue-100/80">
                    KEUANGAN
                </div>

                {{-- Kas Keuangan --}}
                @php $active = $is('kas.*') || $is('keuangan.kas.*'); @endphp
                <a href=""
                    class="flex items-center gap-3 px-3 py-[10px] rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}"
                    :class="{ 'justify-center': collapsed }">
                    <i class="fa-solid fa-wallet" :class="collapsed ? 'text-lg' : ''"></i>
                    <span x-show="!collapsed" class="font-medium">Kas Keuangan</span>
                </a>

                {{-- Catatan Penggajian --}}
                @php $active = $is('penggajian.*'); @endphp
                <a href=""
                    class="mt-2 flex items-center gap-3 px-3 py-[10px] rounded-md transition {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}"
                    :class="{ 'justify-center': collapsed }">
                    <i class="fa-solid fa-file-invoice-dollar" :class="collapsed ? 'text-lg' : ''"></i>
                    <span x-show="!collapsed" class="font-medium">Gaji Karyawan</span>
                </a>
            </div>


        </div>
</aside>
