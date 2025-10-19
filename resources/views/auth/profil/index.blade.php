@extends('layouts.app')

@section('title','Profil')

@section('content')
    <style>[x-cloak]{display:none!important;}</style>

    <div class="space-y-6 w-full">
        {{-- BREADCRUMB --}}
        <div>
            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-[#334976] font-medium transition-colors">
                <i class="fa-solid fa-arrow-left text-gray-600 hover:text-[#334976]"></i>
                <span>Kembali</span>
            </a>
        </div>

        {{-- PROFILE CARD --}}
        <div class="bg-white border border-slate-200 rounded-xl p-6 w-full">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div class="flex items-center gap-4">
                    {{-- Avatar --}}
                    <div class="w-28 h-28 rounded-full overflow-hidden bg-slate-100 flex items-center justify-center">
                        @php
                            $user = auth()->user();
                            $avatar = optional($user)->avatar ? asset('storage/' . $user->avatar) : null;
                        @endphp

                        @if($avatar)
                            <img src="{{ $avatar }}" alt="Avatar {{ $user->name }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-[#334976] text-white">
                                <span class="text-3xl font-semibold">{{ strtoupper(substr($user->name ?? 'U',0,1)) }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Name + meta --}}
                    <div>
                        <h2 class="text-2xl font-semibold text-slate-800">{{ $user->name ?? '-' }}</h2>
                        <div class="text-sm text-slate-500 mt-1">
                            {{ $user->email ?? '-' }}
                        </div>

                        @if($user->roles->isNotEmpty())
                            <div class="mt-2 inline-flex items-center px-3 py-1 rounded-md bg-[#F1F5F9] text-slate-700 text-sm">
                                <i class="fa-solid fa-user-shield mr-2"></i>
                                <span>{{ $user->roles->first()->name }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- LOGOUT BUTTON --}}
                <div class="flex items-center gap-3">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="px-4 py-2 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 inline-flex items-center gap-2 transition-colors">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </button>
                    </form>
                </div>
            </div>

            {{-- DETAILS --}}
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-slate-500 mb-1">Nama Lengkap</label>
                        <div class="text-slate-700 font-medium">{{ $user->name ?? '-' }}</div>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-500 mb-1">Username</label>
                        <div class="text-slate-700 font-medium">{{ $user->username ?? '-' }}</div>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-500 mb-1">Email</label>
                        <div class="text-slate-700 font-medium">{{ $user->email ?? '-' }}</div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-slate-500 mb-1">Role / Hak Akses</label>
                        <div class="text-slate-700 font-medium">
                            @if($user->roles->isNotEmpty())
                                {{ ucwords(str_replace('-', ' ', $user->roles->first()->name)) }}
                            @else
                                -
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-500 mb-1">Member Sejak</label>
                        <div class="text-slate-700 font-medium">
                            {{ $user->created_at ? $user->created_at->format('d M Y') : '-' }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-500 mb-1">Login Terakhir</label>
                        <div class="text-slate-700 font-medium">
                            @if($user->last_login)
                                <span class="text-slate-600">{{ $user->last_login->diffForHumans() }}</span>
                                <span class="text-slate-400 text-sm ml-1">({{ $user->last_login->format('d M Y, H:i') }})</span>
                            @else
                                <span class="text-slate-400">Belum ada data</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- PERMISSIONS SECTION (Optional) --}}
            @if($user->roles->isNotEmpty() && $user->getAllPermissions()->isNotEmpty())
                <div class="mt-6 pt-6 border-t border-slate-200">
                    <label class="block text-sm text-slate-500 mb-3">Hak Akses</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($user->getAllPermissions()->take(10) as $permission)
                            <span class="px-3 py-1 bg-slate-100 text-slate-600 text-xs rounded-full">
                                {{ $permission->name }}
                            </span>
                        @endforeach
                        @if($user->getAllPermissions()->count() > 10)
                            <span class="px-3 py-1 bg-slate-100 text-slate-600 text-xs rounded-full">
                                +{{ $user->getAllPermissions()->count() - 10 }} lainnya
                            </span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection