{{-- resources/views/components/sidebar.blade.php --}}
@php
    $brand = '#4BAC87'; // aksen hijau tetap dipakai untuk fokus / ring
    $navBG = '#344579'; // biru sidebar
    $is = fn($p) => request()->routeIs($p);
    $open = fn($ar) => collect($ar)->contains(fn($p) => request()->routeIs($p));
@endphp

<aside class="w-[250px] bg-[#344579] text-white flex flex-col" style="background-color: {{ $navBG }}">
    {{-- ===== Profil Akun (TOP) ===== --}}
    {{-- HEADER LOGO --}}
    <div class="px-4 h-[56px] flex items-center border-b border-white/10">
        <div class="flex items-center">
            <div class="text-white text-center font-extrabold tracking-wide">
                CV. CAHAYA LOWA
            </div>
        </div>
    </div>


    {{-- ===== NAV ===== --}}
    <div class="flex-1 overflow-y-auto px-4 pb-4 mt-4 space-y-7">

        {{-- ===== UTAMA ===== --}}
        <div>
            <div class="text-[11px] font-semibold tracking-wider mb-3 text-blue-100/80">UTAMA</div>

            {{-- Dashboard --}}
            @php $active = $is('dashboard'); @endphp
            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-3 px-3 py-[10px] rounded-md transition
                {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}">
                {{-- ikon solid saat aktif; jika regular tidak ada, biarkan solid + opacity --}}
                <i class="{{ $active ? 'fa-solid' : 'fa-solid opacity-60' }} fa-house"></i>
                <span class="font-medium">Dashboard</span>
            </a>

            {{-- Penjualan --}}
            @php $active = $is('penjualan.*'); @endphp
            <a href="{{ route('penjualan.index') }}"
                class="mt-2 flex items-center gap-3 px-3 py-[10px] rounded-md transition
                {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}">
                <i class="{{ $active ? 'fa-solid' : 'fa-solid opacity-60' }} fa-cash-register"></i>
                <span class="font-medium">Penjualan</span>
            </a>

            {{-- Pembelian (submenu) --}}
            @php $openPembelian = $open(['pembelian.*','pemesanan.*','retur-pembelian.*','tagihan.*']); @endphp
            <div x-data="{ open: {{ $openPembelian ? 'true' : 'false' }} }" class="mt-2">
                <button @click="open=!open"
                    class="w-full flex items-center gap-3 px-3 py-[10px] rounded-md transition
                       text-white/85 hover:bg-white/5">
                    <i class="fa-solid fa-bag-shopping opacity-60"></i>
                    <span class="flex-1 text-left font-medium">Pembelian</span>
                    <i class="fa-solid fa-chevron-down text-white/60 transition-transform duration-200"
                        :class="open ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="open" x-transition class="mt-1 pl-6 space-y-1">
                    {{-- Daftar Pemesanan --}}
                    @php $on = $is('pemesanan.*'); @endphp
                    <a href="#"
                        class="block px-3 py-2 rounded-md text-[13px] transition
                    {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                        <span class="flex items-center gap-3">
                            <span
                                class="inline-block w-[3px] h-5 rounded
                            {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                            <span>Daftar Pemesanan</span>
                        </span>
                    </a>

                    {{-- Daftar Pembelian --}}
                    @php $on = $is('pembelian.*'); @endphp
                    <a href="#"
                        class="block px-3 py-2 rounded-md text-[13px] transition
                    {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                        <span class="flex items-center gap-3">
                            <span
                                class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                            <span>Daftar Pembelian</span>
                        </span>
                    </a>

                    {{-- Retur Pembelian --}}
                    @php $on = $is('retur-pembelian.*'); @endphp
                    <a href="#"
                        class="block px-3 py-2 rounded-md text-[13px] transition
                    {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                        <span class="flex items-center gap-3">
                            <span
                                class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                            <span>Retur Pembelian</span>
                        </span>
                    </a>

                    {{-- Daftar Tagihan --}}
                    @php $on = $is('tagihan.*'); @endphp
                    <a href="#"
                        class="block px-3 py-2 rounded-md text-[13px] transition
                    {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                        <span class="flex items-center gap-3">
                            <span
                                class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                            <span>Daftar Tagihan</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>

        {{-- ===== MANAJEMEN TOKO ===== --}}
        <div class="pt-4 border-t border-white/10">
            <div class="text-[11px] font-semibold tracking-wider mb-3 text-blue-100/80">MANAJEMEN TOKO</div>

            {{-- Supplier --}}
            @php $active = $is('supplier.*'); @endphp
            <a href="#"
                class="flex items-center gap-3 px-3 py-[10px] rounded-md transition
                {{ $active ? 'bg-white text-[#344579]' : 'text-white/85 hover:bg-white/5' }}">
                <i class="{{ $active ? 'fa-solid' : 'fa-solid opacity-60' }} fa-house-chimney-medical"></i>
                <span class="font-medium">Supplier</span>
            </a>

            {{-- Item --}}
            @php $openItem = $open(['item.*']); @endphp
            <div x-data="{ open: {{ $openItem ? 'true' : 'false' }} }" class="mt-2">
                <button @click="open=!open"
                    class="w-full flex items-center gap-3 px-3 py-[10px] rounded-md text-white/85 hover:bg-white/5">
                    <i class="fa-solid fa-boxes-stacked opacity-60"></i>
                    <span class="flex-1 text-left font-medium">Item</span>
                    <i class="fa-solid fa-chevron-down text-white/60 transition-transform duration-200"
                        :class="open ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="open" x-transition class="mt-1 pl-6 space-y-1">
                    @php $on = $is('item.index'); @endphp
                    <a href="#"
                        class="block px-3 py-2 rounded-md text-[13px] transition
                    {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                        <span class="flex items-center gap-3">
                            <span
                                class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                            <span>Daftar Item</span>
                        </span>
                    </a>
                </div>
            </div>

            {{-- Gudang --}}
            @php $openGudang = $open(['gudang.*']); @endphp
            <div x-data="{ open: {{ $openGudang ? 'true' : 'false' }} }" class="mt-2">
                <button @click="open=!open"
                    class="w-full flex items-center gap-3 px-3 py-[10px] rounded-md text-white/85 hover:bg-white/5">
                    <i class="fa-solid fa-warehouse opacity-60"></i>
                    <span class="flex-1 text-left font-medium">Gudang</span>
                    <i class="fa-solid fa-chevron-down text-white/60 transition-transform duration-200"
                        :class="open ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="open" x-transition class="mt-1 pl-6 space-y-1">
                    @php $on = $is('gudang.index'); @endphp
                    <a href="#"
                        class="block px-3 py-2 rounded-md text-[13px] transition
                    {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                        <span class="flex items-center gap-3">
                            <span
                                class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                            <span>Stok & Lokasi</span>
                        </span>
                    </a>
                </div>
            </div>

            {{-- Pelanggan --}}
            @php $openCust = $open(['pelanggan.*']); @endphp
            <div x-data="{ open: {{ $openCust ? 'true' : 'false' }} }" class="mt-2">
                <button @click="open=!open"
                    class="w-full flex items-center gap-3 px-3 py-[10px] rounded-md text-white/85 hover:bg-white/5">
                    <i class="fa-regular fa-user opacity-80"></i>
                    <span class="flex-1 text-left font-medium">Pelanggan</span>
                    <i class="fa-solid fa-chevron-down text-white/60 transition-transform duration-200"
                        :class="open ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="open" x-transition class="mt-1 pl-6 space-y-1">
                    @php $on = $is('pelanggan.index'); @endphp
                    <a href="#"
                        class="block px-3 py-2 rounded-md text-[13px] transition
                    {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                        <span class="flex items-center gap-3">
                            <span
                                class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                            <span>Daftar Pelanggan</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>

        {{-- ===== MANAJEMEN PENGGUNA ===== --}}
        <div class="pt-4 border-t border-white/10">
            <div class="text-[11px] font-semibold tracking-wider mb-3 text-blue-100/80">MANAJEMEN PENGGUNA</div>

            @php $openUser = $open(['user.*','candidate.*','personalia.*']); @endphp
            <div x-data="{ open: {{ $openUser ? 'true' : 'false' }} }">
                <button @click="open=!open"
                    class="w-full flex items-center gap-3 px-3 py-[10px] rounded-md
                       text-white/90 hover:bg-white/5">
                    <i class="fa-solid fa-user-plus opacity-80"></i>
                    <span class="flex-1 text-left font-semibold">Manajemen Pengguna</span>
                    <i class="fa-solid fa-chevron-down text-white/60 transition-transform duration-200"
                        :class="open ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="open" x-transition class="mt-1 pl-6 space-y-1">
                    <a href="#" class="block px-3 py-2 rounded-md text-[13px] text-white/80 hover:bg-white/5">
                        <span class="flex items-center gap-3">
                            <span class="inline-block w-[3px] h-5 rounded bg-transparent"></span>
                            <span>Candidate Requirement</span>
                        </span>
                    </a>

                    @php $on = $is('candidate.*'); @endphp
                    <a href="#"
                        class="block px-3 py-2 rounded-md text-[13px] transition
                    {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                        <span class="flex items-center gap-3">
                            <span
                                class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                            <span>Candidate</span>
                        </span>
                    </a>

                    @php $on = $is('personalia.*'); @endphp
                    <a href="#"
                        class="block px-3 py-2 rounded-md text-[13px] transition
                    {{ $on ? 'bg-white text-[#344579] font-semibold' : 'text-white/80 hover:bg-white/5' }}">
                        <span class="flex items-center gap-3">
                            <span
                                class="inline-block w-[3px] h-5 rounded {{ $on ? 'bg-white' : 'bg-transparent' }}"></span>
                            <span>Personalia</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</aside>
