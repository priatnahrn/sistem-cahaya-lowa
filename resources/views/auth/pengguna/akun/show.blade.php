@extends('layouts.app')

@section('title', 'Detail & Edit Pengguna')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <div x-data="userEditPage()" x-init="init()" class="space-y-6">

        {{-- 🔙 Tombol Kembali --}}
        <div>
            <a href="{{ route('users.index') }}"
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

        {{-- 📦 Card Informasi Pengguna --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-4">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-user text-[#344579]"></i>
                Informasi Pengguna
            </h3>
            <div class="space-y-4">
                {{-- Nama Lengkap --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Nama Lengkap <span class="text-red-500">*</span>
                    </label>
                    <input type="text" x-model="form.name" placeholder="Contoh: John Doe"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg
                           focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-200': errors.name }">
                    <p x-show="errors.name" x-text="errors.name" class="text-red-500 text-xs mt-1"></p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Username --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            Username <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.username" placeholder="Minimal 6 karakter"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-lg
                               focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                            :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-200': errors.username }">
                        <p x-show="errors.username" x-text="errors.username" class="text-red-500 text-xs mt-1"></p>
                    </div>

                    {{-- phone --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            Phone <span class="text-slate-400 text-xs">(Opsional)</span>
                        </label>
                        <input type="tel" x-model="form.phone" placeholder="contoh@phone.com"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-lg
                               focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                            :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-200': errors.phone }">
                        <p x-show="errors.phone" x-text="errors.phone" class="text-red-500 text-xs mt-1"></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- 🔑 Card Ubah Password (Optional) --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-key text-[#344579]"></i>
                        Ubah Password
                    </h3>
                    <p class="text-sm text-slate-500 mt-1">Kosongkan jika tidak ingin mengubah password</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="changePassword" class="sr-only peer">
                    <div
                        class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 
                        rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white 
                        after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white 
                        after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all 
                        peer-checked:bg-blue-600">
                    </div>
                    <span class="ml-3 text-sm font-medium text-slate-700">Aktifkan</span>
                </label>
            </div>

            <div x-show="changePassword" x-collapse class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Password Baru --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            Password Baru <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" x-model="form.password"
                                placeholder="Minimal 8 karakter"
                                class="w-full px-3 py-2.5 border border-slate-300 rounded-lg pr-10
                                   focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-200': errors.password }">
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <i class="fa-solid" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                        <p x-show="errors.password" x-text="errors.password" class="text-red-500 text-xs mt-1"></p>
                    </div>

                    {{-- Konfirmasi Password --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            Konfirmasi Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showPasswordConfirm ? 'text' : 'password'"
                                x-model="form.password_confirmation" placeholder="Ulangi password"
                                class="w-full px-3 py-2.5 border border-slate-300 rounded-lg pr-10
                                   focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                :class="{
                                    'border-red-300 focus:border-red-500 focus:ring-red-200': errors
                                        .password_confirmation
                                }">
                            <button type="button" @click="showPasswordConfirm = !showPasswordConfirm"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <i class="fa-solid" :class="showPasswordConfirm ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                        <p x-show="errors.password_confirmation" x-text="errors.password_confirmation"
                            class="text-red-500 text-xs mt-1"></p>
                    </div>
                </div>

                <div class="flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                    <i class="fa-solid fa-info-circle text-amber-600 mt-0.5"></i>
                    <div class="text-sm text-amber-700">
                        <p class="font-medium">Tips Keamanan:</p>
                        <ul class="mt-1 list-disc list-inside space-y-1">
                            <li>Gunakan kombinasi huruf besar, kecil, angka, dan simbol</li>
                            <li>Minimal 8 karakter</li>
                            <li>Jangan gunakan informasi pribadi yang mudah ditebak</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- 🔐 Card Roles --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                <div>
                    <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-[#344579]"></i>
                        Role Pengguna
                    </h3>
                    <p class="text-sm text-slate-600 mt-1">Pilih role untuk menentukan hak akses pengguna</p>
                </div>
                <div class="px-3 py-1.5 bg-[#344579] text-white rounded-lg text-sm font-medium">
                    <span x-text="selectedRolesCount()"></span> dipilih
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <template x-for="role in allRoles" :key="role.id">
                        <label
                            class="flex items-center gap-3 px-4 py-3 rounded-lg border border-slate-200 hover:border-blue-300 hover:bg-blue-50 cursor-pointer transition"
                            :class="{ 'bg-blue-50 border-blue-300': form.roles.includes(role.id) }">
                            <input type="checkbox" :value="role.id" x-model="form.roles"
                                class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-2 focus:ring-blue-200">
                            <div class="flex-1">
                                <div class="font-medium text-slate-800" x-text="role.name"></div>
                                <div class="text-xs text-slate-500">
                                    <span x-text="role.permissions_count"></span> permissions
                                </div>
                            </div>
                            <i class="fa-solid fa-shield-halved text-slate-400"
                                :class="{ 'text-blue-500': form.roles.includes(role.id) }"></i>
                        </label>
                    </template>
                </div>

                <div x-show="allRoles.length === 0" class="text-center py-8 text-slate-400">
                    <i class="fa-solid fa-shield text-4xl mb-2"></i>
                    <p>Belum ada role tersedia</p>
                </div>
            </div>
        </div>

        {{-- 💾 Footer Sticky --}}
        <div class="sticky bottom-0 bg-gradient-to-t from-white via-white to-transparent pt-4 pb-2">
            <div class="bg-white border border-slate-200 rounded-xl px-6 py-4 shadow-lg">
                <div class="flex justify-between items-center">
                    <div class="text-sm text-slate-600">
                        <span class="font-semibold" x-text="selectedRolesCount()"></span> role dipilih
                        <span x-show="changePassword" class="ml-2 text-amber-600">
                            <i class="fa-solid fa-key"></i> Password akan diubah
                        </span>
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('users.index') }}"
                            class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition font-medium">
                            Batal
                        </a>
                        <button type="button" @click="save()" :disabled="!isValid()"
                            class="px-5 py-2.5 rounded-lg text-white font-medium transition"
                            :class="isValid() ?
                                'bg-[#334976] hover:bg-[#2d3f6d] cursor-pointer shadow-sm hover:shadow-md' :
                                'bg-gray-300 cursor-not-allowed opacity-60'">
                            <i class="fa-solid fa-save mr-2"></i> Update Pengguna
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @php
        $rolesData = $roles
            ->map(fn($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'permissions_count' => $r->permissions()->count(),
            ])
            ->toArray();

        $userRoles = $user->roles->pluck('id')->toArray();
    @endphp

    <script>
        function userEditPage() {
            return {
                userId: {{ $user->id }},
                form: {
                    name: '{{ $user->name }}',
                    username: '{{ $user->username }}',
                    phone: '{{ $user->phone ?? '' }}',
                    password: '',
                    password_confirmation: '',
                    roles: @json($userRoles)
                },

                changePassword: false,
                errors: {},
                allRoles: @json($rolesData),
                showPassword: false,
                showPasswordConfirm: false,

                notifMessage: '',
                notifType: '',
                showNotif: false,

                init() {
                    console.log('✅ Editing user ID:', this.userId);
                    console.log('✅ Loaded', this.allRoles.length, 'roles');

                    // Watch changePassword toggle
                    this.$watch('changePassword', (value) => {
                        if (!value) {
                            // Clear password fields when toggle off
                            this.form.password = '';
                            this.form.password_confirmation = '';
                            delete this.errors.password;
                            delete this.errors.password_confirmation;
                        }
                    });
                },

                selectedRolesCount() {
                    return this.form.roles.length;
                },

                isValid() {
                    // Basic validation
                    const basicValid = (
                        this.form.name.trim() !== '' &&
                        this.form.username.trim().length >= 6
                    );

                    // phone validation (optional but must be valid if filled)
                    let phoneValid = true;
                    if (this.form.phone.trim() !== '') {
                        const phoneRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        phoneValid = phoneRegex.test(this.form.phone);
                    }

                    // Password validation (only if changePassword is true)
                    let passwordValid = true;
                    if (this.changePassword) {
                        passwordValid = (
                            this.form.password.length >= 8 &&
                            this.form.password === this.form.password_confirmation
                        );
                    }

                    return basicValid && phoneValid && passwordValid;
                },

                async save() {
                    if (!this.isValid()) {
                        this.notify('Mohon lengkapi semua field dengan benar', 'error');
                        return;
                    }

                    this.errors = {}; // Reset errors

                    // Prepare data to send
                    const payload = {
                        name: this.form.name,
                        username: this.form.username,
                        phone: this.form.phone,
                        roles: this.form.roles,
                        _method: 'PUT'
                    };

                    // Only include password if changePassword is true
                    if (this.changePassword) {
                        payload.password = this.form.password;
                        payload.password_confirmation = this.form.password_confirmation;
                    }

                    try {
                        const res = await fetch(`/users/${this.userId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(payload)
                        });

                        const result = await res.json();

                        if (!res.ok) {
                            // Handle validation errors
                            if (result.errors) {
                                this.errors = result.errors;
                                this.notify('Terdapat kesalahan pada form', 'error');
                            } else {
                                this.notify(result.message || 'Gagal memperbarui pengguna', 'error');
                            }
                            return;
                        }

                        this.notify('Pengguna berhasil diperbarui!', 'success');
                        setTimeout(() => {
                            window.location.href = '{{ route('users.index') }}';
                        }, 1500);

                    } catch (err) {
                        console.error('Error save:', err);
                        this.notify('Terjadi kesalahan saat memperbarui pengguna', 'error');
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