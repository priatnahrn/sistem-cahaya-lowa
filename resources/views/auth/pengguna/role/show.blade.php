@extends('layouts.app')

@section('title', 'Detail Role - ' . $role->name)

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <div x-data="roleShowPage()" x-init="init()" class="space-y-6">

        {{-- 🔙 Tombol Kembali --}}
        <div>
            <a href="{{ route('roles.index') }}"
                class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-[#334976] font-medium transition-colors">
                <i class="fa-solid fa-arrow-left text-gray-600 hover:text-[#334976]"></i>
                <span>Kembali ke Daftar Role</span>
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

        {{-- 📦 Card Header dengan Tombol Edit --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-4">
            <div class="flex justify-between items-start">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-2xl font-bold text-slate-800" x-text="editMode ? 'Edit Role' : form.name"></h2>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                            :class="form.guard_name === 'web' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-purple-50 text-purple-700 border border-purple-200'">
                            <i class="fa-solid" :class="form.guard_name === 'web' ? 'fa-globe' : 'fa-code'"></i>
                            <span x-text="form.guard_name"></span>
                        </span>
                    </div>
                    <div class="mt-2 flex items-center gap-4 text-sm text-slate-600">
                        <span>
                            <i class="fa-solid fa-shield-halved text-slate-400"></i>
                            <span x-text="form.permissions.length"></span> dari <span x-text="allPermissions.length"></span> permission
                        </span>
                        <span>
                            <i class="fa-solid fa-users text-slate-400"></i>
                            <span x-text="usersCount"></span> user
                        </span>
                        <span>
                            <i class="fa-solid fa-calendar text-slate-400"></i>
                            Dibuat: <span x-text="fmtTanggal(createdAt)"></span>
                        </span>
                    </div>
                </div>

                <div class="flex gap-2">
                    <template x-if="!editMode">
                        <button type="button" @click="enableEdit()"
                            class="px-4 py-2 rounded-lg bg-[#334976] text-white hover:bg-[#2d3f6d] transition font-medium shadow-sm hover:shadow-md">
                            <i class="fa-solid fa-edit mr-2"></i> Edit Role
                        </button>
                    </template>
                    
                    <template x-if="editMode">
                        <div class="flex gap-2">
                            <button type="button" @click="cancelEdit()"
                                class="px-4 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition font-medium">
                                <i class="fa-solid fa-xmark mr-2"></i> Batal
                            </button>
                            <button type="button" @click="save()" :disabled="!isValid()"
                                class="px-4 py-2 rounded-lg text-white font-medium transition"
                                :class="isValid() ?
                                    'bg-green-600 hover:bg-green-700 cursor-pointer shadow-sm hover:shadow-md' :
                                    'bg-gray-300 cursor-not-allowed opacity-60'">
                                <i class="fa-solid fa-save mr-2"></i> Simpan Perubahan
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- 📝 Card Informasi Role (Edit Mode) --}}
        <div x-show="editMode" x-cloak x-transition class="bg-white border border-slate-200 rounded-xl px-6 py-4">
            <h3 class="font-semibold text-slate-800 mb-4">Informasi Role</h3>
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
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
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
                        <span x-text="form.permissions.length"></span> / <span x-text="allPermissions.length"></span> dipilih
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

        {{-- 👥 Card Users yang Menggunakan Role Ini --}}
        <div x-show="usersCount > 0" class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                <h3 class="font-semibold text-slate-800">User dengan Role Ini</h3>
                <p class="text-sm text-slate-600 mt-1">Daftar pengguna yang memiliki role <span class="font-semibold" x-text="form.name"></span></p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <template x-for="user in users" :key="user.id">
                        <div class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg hover:bg-slate-50 transition">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-semibold">
                                <span x-text="user.name.charAt(0).toUpperCase()"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-slate-800 truncate" x-text="user.name"></p>
                                <p class="text-xs text-slate-500 truncate" x-text="user.email"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

    </div>

    @php
        $permissionsGrouped = $permissions->flatten()->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'module' => explode('.', $p->name)[0] ?? 'other',
        ])->toArray();

        $roleUsers = $role->users->map(fn($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
        ])->toArray();
    @endphp

    <script>
        function roleShowPage() {
            return {
                editMode: false,
                originalData: null,
                
                form: {
                    name: @json($role->name),
                    guard_name: @json($role->guard_name),
                    permissions: @json($role->permissions->pluck('id')->toArray())
                },

                allPermissions: @json($permissionsGrouped),
                users: @json($roleUsers),
                usersCount: @json($role->users->count()),
                createdAt: @json($role->created_at ? $role->created_at->format('Y-m-d H:i:s') : null),
                
                searchQuery: '',
                notifMessage: '',
                notifType: '',
                showNotif: false,

                // ✅ LENGKAP: Grouping sesuai semua module yang ada
                permissionGroups: {
                    'UTAMA': ['dashboard', 'cek_harga'],
                    'PENJUALAN': ['penjualan', 'pengiriman', 'retur_penjualan'],
                    'KASIR': ['penjualan_cepat', 'pembayaran', 'tagihan_penjualan'],
                    'PEMBELIAN': ['pembelian', 'retur_pembelian', 'tagihan_pembelian'],
                    'MANAJEMEN TOKO': ['gudang', 'supplier', 'items', 'kategori_items', 'pelanggan', 'mutasi_stok', 'produksi'],
                    'MANAJEMEN PENGGUNA': ['roles', 'users', 'activity_logs'],
                    'KEUANGAN': ['kas_keuangan', 'gaji_karyawan', 'cashflows', 'payrolls'], // ✅ Ditambah
                    'LAINNYA': ['profile'],
                },

                moduleTranslations: {
                    'dashboard': 'Dashboard',
                    'cek_harga': 'Cek Harga',
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
                    'gaji_karyawan': 'Gaji Karyawan',
                    'cashflows': 'Kas Keuangan',
                    'kas_keuangan': 'Kas Keuangan',
                    'profile': 'Profil',
                },

                actionTranslations: {
                    'view': 'Lihat',
                    'create': 'Tambah',
                    'update': 'Ubah',
                    'delete': 'Hapus',
                },

                init() {
                    this.originalData = JSON.parse(JSON.stringify(this.form));
                    
                    // ✅ DEBUG: Cek module apa saja yang ada
                    const uniqueModules = [...new Set(this.allPermissions.map(p => p.module))];
                    console.log('📦 Total Permissions:', this.allPermissions.length);
                    console.log('📋 Unique Modules:', uniqueModules);
                    console.log('✅ Selected Permissions:', this.form.permissions.length);
                    
                    // ✅ Cek module yang tidak terdaftar di permissionGroups
                    const allGroupedModules = Object.values(this.permissionGroups).flat();
                    const unmappedModules = uniqueModules.filter(m => !allGroupedModules.includes(m));
                    if (unmappedModules.length > 0) {
                        console.warn('⚠️ Module belum terdaftar di permissionGroups:', unmappedModules);
                    }
                },

                fmtTanggal(iso) {
                    if (!iso) return '-';
                    const d = new Date(iso);
                    if (isNaN(d)) return iso;
                    const dd = String(d.getDate()).padStart(2, '0');
                    const mm = String(d.getMonth() + 1).padStart(2, '0');
                    const yyyy = d.getFullYear();
                    return `${dd}-${mm}-${yyyy}`;
                },

                enableEdit() {
                    this.editMode = true;
                    this.originalData = JSON.parse(JSON.stringify(this.form));
                },

                cancelEdit() {
                    this.form = JSON.parse(JSON.stringify(this.originalData));
                    this.editMode = false;
                },

                groupedPermissions() {
                    const query = this.searchQuery.toLowerCase();
                    let filtered = this.allPermissions;

                    // Filter berdasarkan search query
                    if (query) {
                        filtered = filtered.filter(p => {
                            const moduleName = this.moduleTranslations[p.module] || p.module;
                            const permName = this.formatPermName(p.name);
                            return moduleName.toLowerCase().includes(query) ||
                                   permName.toLowerCase().includes(query) ||
                                   p.name.toLowerCase().includes(query);
                        });
                    }

                    const result = {};
                    
                    // ✅ Loop semua group dan module
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
                    
                    // ✅ TAMBAH: Group "LAIN-LAIN" untuk module yang tidak terdaftar
                    const allGroupedModules = Object.values(this.permissionGroups).flat();
                    const ungroupedPerms = filtered.filter(p => !allGroupedModules.includes(p.module));
                    
                    if (ungroupedPerms.length > 0) {
                        const ungroupedByModule = {};
                        ungroupedPerms.forEach(perm => {
                            if (!ungroupedByModule[perm.module]) {
                                ungroupedByModule[perm.module] = [];
                            }
                            ungroupedByModule[perm.module].push(perm);
                        });
                        
                        if (Object.keys(ungroupedByModule).length > 0) {
                            result['TIDAK TERKATEGORI'] = ungroupedByModule;
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
                        const res = await fetch('{{ route('roles.update', $role->id) }}', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.form)
                        });

                        const result = await res.json();

                        if (!res.ok) {
                            this.notify(result.message || 'Gagal memperbarui role', 'error');
                            return;
                        }

                        this.notify('Role berhasil diperbarui!', 'success');
                        this.editMode = false;
                        this.originalData = JSON.parse(JSON.stringify(this.form));

                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);

                    } catch (err) {
                        console.error('Error save:', err);
                        this.notify('Terjadi kesalahan saat memperbarui role', 'error');
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