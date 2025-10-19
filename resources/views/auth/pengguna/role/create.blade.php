@extends('layouts.app')

@section('title', 'Tambah Role Baru')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <div x-data="roleCreatePage()" x-init="init()" class="space-y-6">

        {{-- 🔙 Tombol Kembali --}}
        <div>
            <a href="{{ route('roles.index') }}"
                class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-[#334976] font-medium transition-colors">
                <i class="fa-solid fa-arrow-left text-gray-600 hover:text-[#334976]"></i>
                <span>Kembali</span>
            </a>
        </div>

        {{-- 🔔 Toast Notification --}}
        <div x-show="showNotif" x-transition class="fixed top-5 right-5 z-50">
            <div :class="{
                'bg-green-500': notifType === 'success',
                'bg-red-500': notifType === 'error',
                'bg-blue-500': notifType === 'info'
            }"
                class="text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2 min-w-[250px]">
                <template x-if="notifType === 'success'">
                    <i class="fa-solid fa-circle-check"></i>
                </template>
                <template x-if="notifType === 'error'">
                    <i class="fa-solid fa-circle-xmark"></i>
                </template>
                <template x-if="notifType === 'info'">
                    <i class="fa-solid fa-circle-info"></i>
                </template>
                <span x-text="notifMessage"></span>
            </div>
        </div>

        {{-- 📦 Card Informasi Role --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-4">
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Nama Role --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            Nama Role <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.name" placeholder="Contoh: Manager, Kasir, Staff"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-lg
                               focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition">
                    </div>

                    {{-- Guard Name --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            Guard <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select x-model="form.guard_name"
                                class="w-full px-3 py-2.5 rounded-lg border border-slate-200
                                   appearance-none pr-8 bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                <option value="web">Web</option>
                                <option value="api">API</option>
                            </select>
                            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 🔐 Card Permissions --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                <div>
                    <h3 class="font-semibold text-slate-800">Hak Akses (Permissions)</h3>
                    <p class="text-sm text-slate-600 mt-1">Pilih hak akses yang dimiliki role ini</p>
                </div>
                <div class="flex items-center gap-3">
                    {{-- Search Permission --}}
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="searchQuery" placeholder="Cari hak akses..."
                            class="w-64 pl-10 pr-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-600 placeholder-slate-400
                               focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                    </div>
                    {{-- Badge Selected Count --}}
                    <div class="px-3 py-1.5 bg-[#344579] text-white rounded-lg text-sm font-medium">
                        <span x-text="selectedCount"></span> dipilih
                    </div>
                </div>
            </div>

            <div class="p-6 space-y-6">
                {{-- Loop per Group --}}
                <template x-for="(group, groupName) in groupedPermissions()" :key="groupName">
                    <div class="border border-slate-200 rounded-lg overflow-hidden">
                        {{-- Group Header --}}
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 px-5 py-3 border-b border-blue-200">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-layer-group text-blue-600"></i>
                                <h3 class="font-bold text-slate-800 uppercase text-xs tracking-wider" x-text="groupName"></h3>
                                <span class="text-xs text-slate-500 bg-white px-2.5 py-0.5 rounded-full font-medium"
                                    x-text="countGroupPermissions(group) + ' akses'"></span>
                            </div>
                        </div>

                        {{-- Loop per Module dalam Group --}}
                        <div class="divide-y divide-slate-100">
                            <template x-for="(perms, module) in group" :key="module">
                                <div class="bg-white">
                                    {{-- Module Header --}}
                                    <div class="bg-slate-50 px-4 py-2.5 flex justify-between items-center">
                                        <div class="flex items-center gap-2.5">
                                            <i class="fa-solid fa-cube text-[#344579] text-sm"></i>
                                            <h4 class="font-semibold text-slate-700 text-sm" x-text="formatModuleName(module)"></h4>
                                        </div>
                                        <button type="button" @click="toggleModule(module, perms)"
                                            class="text-xs font-medium px-2.5 py-1 rounded-md transition"
                                            :class="isModuleSelected(module, perms) ? 
                                                'bg-red-100 text-red-600 hover:bg-red-200' : 
                                                'bg-blue-100 text-blue-600 hover:bg-blue-200'">
                                            <i class="fa-solid text-[10px]"
                                                :class="isModuleSelected(module, perms) ? 'fa-xmark' : 'fa-check'"></i>
                                            <span x-text="isModuleSelected(module, perms) ? 'Batal' : 'Pilih'"></span>
                                        </button>
                                    </div>

                                    {{-- Permission Checkboxes --}}
                                    <div class="p-4 grid grid-cols-2 md:grid-cols-4 gap-2.5">
                                        <template x-for="perm in perms" :key="perm.id">
                                            <label
                                                class="flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 hover:border-blue-300 hover:bg-blue-50 cursor-pointer transition"
                                                :class="{ 'bg-blue-50 border-blue-300': form.permissions.includes(perm.id) }">
                                                <input type="checkbox" :value="perm.id" x-model="form.permissions"
                                                    class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-2 focus:ring-blue-200">
                                                <span class="text-sm text-slate-700 font-medium" x-text="formatPermName(perm.name)"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- No Results --}}
                <div x-show="Object.keys(groupedPermissions()).length === 0" x-cloak
                    class="text-center py-8 text-slate-400">
                    <i class="fa-solid fa-search text-4xl mb-2"></i>
                    <p>Tidak ada hak akses yang ditemukan</p>
                </div>
            </div>
        </div>

        {{-- 💾 Footer Sticky --}}
        <div class="sticky bottom-0 bg-gradient-to-t from-white via-white to-transparent pt-4 pb-2">
            <div class="bg-white border border-slate-200 rounded-xl px-6 py-4 shadow-lg">
                <div class="flex justify-between items-center">
                    <div class="text-sm text-slate-600">
                        <span class="font-semibold" x-text="selectedCount"></span> hak akses dipilih
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('roles.index') }}"
                            class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition font-medium">
                            Batal
                        </a>
                        <button type="button" @click="save()" :disabled="!isValid()"
                            class="px-5 py-2.5 rounded-lg text-white font-medium transition"
                            :class="isValid() ?
                                'bg-[#334976] hover:bg-[#2d3f6d] cursor-pointer shadow-sm hover:shadow-md' :
                                'bg-gray-300 cursor-not-allowed opacity-60'">
                            <i class="fa-solid fa-save mr-2"></i> Simpan Role
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @php
        $permissionsGrouped = $permissions
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'module' => explode('.', $p->name)[0] ?? 'other',
            ])
            ->toArray();
    @endphp

    <script>
        function roleCreatePage() {
            return {
                form: {
                    name: '',
                    guard_name: 'web',
                    permissions: []
                },

                allPermissions: @json($permissionsGrouped),
                searchQuery: '',

                notifMessage: '',
                notifType: '',
                showNotif: false,

                // Grouping sesuai sidebar
                permissionGroups: {
                    'UTAMA': ['dashboard'],
                    'PENJUALAN': ['penjualan', 'pengiriman', 'retur_penjualan'],
                    'KASIR': ['penjualan_cepat', 'pembayaran', 'tagihan_penjualan'],
                    'PEMBELIAN': ['pembelian', 'retur_pembelian', 'tagihan_pembelian'],
                    'MANAJEMEN TOKO': ['gudang', 'supplier', 'items', 'kategori_items', 'pelanggan', 'mutasi_stok', 'produksi'],
                    'MANAJEMEN PENGGUNA': ['roles', 'users', 'activity_logs'],
                    'KEUANGAN': ['kas_keuangan', 'gaji_karyawan'],
                    'LAINNYA': [ 'profile'],
                },

                // Mapping nama module ke Bahasa Indonesia
                moduleTranslations: {
                    'dashboard': 'Dashboard',
                    'penjualan': 'Daftar Penjualan',
                    'penjualan_cepat': 'Penjualan Cepat',
                    'retur_penjualan': 'Retur Penjualan',
                    'tagihan_penjualan': 'Tagihan Penjualan',
                    'pengiriman': 'Daftar Pengiriman',
                    'pembayaran': 'Pembayaran',
                    'pembelian': 'Daftar Pembelian',
                    'tagihan_pembelian': 'Daftar Tagihan',
                    'retur_pembelian': 'Retur Pembelian',
                    'gudang': 'Gudang',
                    'supplier': 'Supplier',
                    'pelanggan': 'Pelanggan',
                    'items': 'Daftar Item',
                    'kategori_items': 'Kategori Item',
                    'produksi': 'Produksi',
                    'mutasi_stok': 'Mutasi Stok',
                    'users': 'Daftar Akun',
                    'roles': 'Role',
                    'activity_logs': 'Log Aktivitas',
                    'payrolls': 'Gaji Karyawan',
                    'cashflows': 'Kas Keuangan',
                    'profile': 'Profil',
                },

                // Mapping action ke Bahasa Indonesia
                actionTranslations: {
                    'view': 'Lihat',
                    'create': 'Tambah',
                    'update': 'Ubah',
                    'delete': 'Hapus',
                },

                init() {
                    console.log('✅ Loaded', this.allPermissions.length, 'permissions');
                },

                get selectedCount() {
                    return this.form.permissions.length;
                },

                groupedPermissions() {
                    const query = this.searchQuery.toLowerCase();
                    let filtered = this.allPermissions;

                    if (query) {
                        filtered = filtered.filter(p => {
                            const moduleName = this.moduleTranslations[p.module] || p.module;
                            const permName = this.formatPermName(p.name);
                            return moduleName.toLowerCase().includes(query) ||
                                   permName.toLowerCase().includes(query) ||
                                   p.name.toLowerCase().includes(query);
                        });
                    }

                    // Group by category first, then by module
                    const result = {};
                    
                    for (const [groupName, modules] of Object.entries(this.permissionGroups)) {
                        const groupModules = {};
                        
                        modules.forEach(module => {
                            const modulePerms = filtered.filter(p => p.module === module);
                            if (modulePerms.length > 0) {
                                groupModules[module] = modulePerms;
                            }
                        });
                        
                        if (Object.keys(groupModules).length > 0) {
                            result[groupName] = groupModules;
                        }
                    }
                    
                    return result;
                },

                countGroupPermissions(group) {
                    let count = 0;
                    for (const perms of Object.values(group)) {
                        count += perms.length;
                    }
                    return count;
                },

                formatModuleName(module) {
                    return this.moduleTranslations[module] || module.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                },

                formatPermName(name) {
                    const parts = name.split('.');
                    if (parts.length > 1) {
                        const action = parts[1];
                        return this.actionTranslations[action] || action.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    }
                    return name;
                },

                toggleModule(module, perms) {
                    const ids = perms.map(p => p.id);
                    const allSelected = ids.every(id => this.form.permissions.includes(id));

                    if (allSelected) {
                        this.form.permissions = this.form.permissions.filter(id => !ids.includes(id));
                    } else {
                        ids.forEach(id => {
                            if (!this.form.permissions.includes(id)) {
                                this.form.permissions.push(id);
                            }
                        });
                    }
                },

                isModuleSelected(module, perms) {
                    const ids = perms.map(p => p.id);
                    return ids.every(id => this.form.permissions.includes(id));
                },

                isValid() {
                    return this.form.name.trim() !== '' && this.form.guard_name !== '';
                },

                async save() {
                    if (!this.isValid()) {
                        this.notify('Nama role wajib diisi', 'error');
                        return;
                    }

                    try {
                        const res = await fetch('{{ route('roles.store') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.form)
                        });

                        const result = await res.json();

                        if (!res.ok) {
                            this.notify(result.message || 'Gagal menyimpan role', 'error');
                            return;
                        }

                        this.notify('Role berhasil ditambahkan!', 'success');
                        setTimeout(() => {
                            window.location.href = '{{ route('roles.index') }}';
                        }, 1500);

                    } catch (err) {
                        console.error('Error save:', err);
                        this.notify('Terjadi kesalahan saat menyimpan role', 'error');
                    }
                },

                notify(msg, type = 'info') {
                    this.notifMessage = msg;
                    this.notifType = type;
                    this.showNotif = true;
                    setTimeout(() => (this.showNotif = false), 3000);
                }
            };
        }
    </script>

@endsection