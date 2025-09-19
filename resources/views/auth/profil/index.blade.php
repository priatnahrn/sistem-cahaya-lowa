@extends('layouts.app')

@section('title','Profil')

@section('content')
    <style>[x-cloak]{display:none!important;}</style>

    <div class="space-y-6 w-full">
        {{-- BREADCRUMB --}}
        <div class="flex items-center gap-3">
            <a href="{{ url('/') }}" class="text-slate-500 hover:underline text-sm">Dashboard</a>
            <div class="text-sm text-slate-400">/</div>
            <div class="inline-flex items-center text-sm">
                <span class="px-3 py-1 rounded-md bg-[#E9F3FF] text-[#1D4ED8] border border-[#BFDBFE] font-medium">
                    Profil
                </span>
            </div>
        </div>

        {{-- PROFILE CARD --}}
        <div class="bg-white border border-slate-200 rounded-xl p-6 w-full">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div class="flex items-center gap-4">
                    {{-- Avatar --}}
                    <div class="w-28 h-28 rounded-full overflow-hidden bg-slate-100 flex items-center justify-center">
                        @php
                            $user = auth()->user();
                            // jika Anda menyimpan path avatar di $user->avatar
                            $avatar = optional($user)->avatar ? asset('storage/' . $user->avatar) : null;
                        @endphp

                        @if($avatar)
                            <img src="{{ $avatar }}" alt="Avatar {{ $user->name }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-slate-400">
                                <span class="text-xl font-semibold">{{ strtoupper(substr($user->name ?? 'U',0,1)) }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Name + meta --}}
                    <div>
                        <h2 class="text-2xl font-semibold text-slate-800">{{ $user->name ?? '-' }}</h2>
                        <div class="text-sm text-slate-500 mt-1">
                            {{ $user->email ?? '-' }}
                        </div>

                        @if(isset($user->role))
                            <div class="mt-2 inline-flex items-center px-3 py-1 rounded-md bg-[#F1F5F9] text-slate-700 text-sm">
                                <i class="fa-solid fa-user-shield mr-2"></i>
                                <span>{{ ucfirst($user->role) }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ACTIONS --}}
                <div class="flex items-center gap-3">
                    <a href="" 
                       class="px-4 py-2 rounded-lg text-white bg-[#344579] hover:bg-[#2e3e6a] shadow inline-flex items-center gap-2">
                        <i class="fa-solid fa-pen"></i> Edit Profil
                    </a>

                    <a href="" 
                       class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 inline-flex items-center gap-2">
                        <i class="fa-solid fa-key"></i> Ubah Password
                    </a>

                    {{-- logout via form POST --}}
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="px-4 py-2 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 inline-flex items-center gap-2">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </button>
                    </form>
                </div>
            </div>

            {{-- DETAILS --}}
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm text-slate-500 mb-1">Nama</label>
                        <div class="text-slate-700 font-medium">{{ $user->name ?? '-' }}</div>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-500 mb-1">Email</label>
                        <div class="text-slate-700 font-medium">{{ $user->email ?? '-' }}</div>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-500 mb-1">Nomor Telepon</label>
                        <div class="text-slate-700 font-medium">{{ $user->phone ?? '-' }}</div>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-500 mb-1">Alamat</label>
                        <div class="text-slate-700">{{ $user->address ?? '-' }}</div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-sm text-slate-500 mb-1">Role / Jabatan</label>
                        <div class="text-slate-700 font-medium">{{ $user->role ? ucfirst($user->role) : '-' }}</div>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-500 mb-1">Member Sejak</label>
                        <div class="text-slate-700 font-medium">
                            {{ $user->created_at ? $user->created_at->format('d M Y') : '-' }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-500 mb-1">Last Login</label>
                        <div class="text-slate-700 font-medium">
                            {{-- jika Anda menyimpan kolom last_login --}}
                            {{ $user->last_login ? $user->last_login->diffForHumans() . ' (' . $user->last_login->format('d M Y H:i') . ')' : '-' }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-500 mb-1">Catatan</label>
                        <div class="text-slate-700">
                            {{ $user->notes ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
