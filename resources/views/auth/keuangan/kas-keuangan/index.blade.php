@extends('layouts.app')

@section('title', 'Kas Keuangan')

@section('content')
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        @keyframes pulse-glow {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }

        .float-animation {
            animation: float 3s ease-in-out infinite;
        }

        .pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }
    </style>

    <div class="min-h-[calc(100vh-200px)] flex items-center justify-center">
        <div class="max-w-2xl w-full mx-auto px-6">
            
            {{-- Coming Soon Card --}}
            <div class="bg-gradient-to-br from-white via-slate-50 to-blue-50 border border-slate-200 rounded-2xl shadow-xl overflow-hidden">
                
                {{-- Decorative Header --}}
                <div class="relative bg-gradient-to-r from-[#344579] to-[#4a5f9f] px-8 py-12 text-center overflow-hidden">
                    {{-- Background Pattern --}}
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute top-0 left-0 w-40 h-40 bg-white rounded-full blur-3xl"></div>
                        <div class="absolute bottom-0 right-0 w-60 h-60 bg-white rounded-full blur-3xl"></div>
                    </div>

                    {{-- Icon --}}
                    <div class="relative mb-6 float-animation">
                        <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-white/20 backdrop-blur-sm border border-white/30 shadow-lg">
                            <i class="fa-solid fa-wallet text-5xl text-white"></i>
                        </div>
                    </div>

                    {{-- Title --}}
                    <h1 class="relative text-4xl font-bold text-white mb-3">
                        Kas Keuangan
                    </h1>
                    <p class="relative text-lg text-blue-100">
                        Sistem Manajemen Keuangan
                    </p>
                </div>

                {{-- Content --}}
                <div class="px-8 py-10 text-center space-y-6">
                    {{-- Coming Soon Badge --}}
                    <div class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-amber-50 to-yellow-50 border border-amber-200 rounded-full">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                        </span>
                        <span class="text-amber-700 font-semibold text-lg">Coming Soon</span>
                    </div>

                    {{-- Description --}}
                    <div class="space-y-3">
                        <p class="text-slate-600 text-lg leading-relaxed">
                            Fitur <span class="font-semibold text-slate-800">Kas Keuangan</span> sedang dalam tahap pengembangan.
                        </p>
                        <p class="text-slate-500">
                            Kami sedang mempersiapkan sistem yang lengkap untuk mengelola kas masuk, kas keluar, dan laporan keuangan Anda.
                        </p>
                    </div>

                    {{-- Features Preview --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
                        <div class="bg-white border border-slate-200 rounded-xl p-4 hover:shadow-md transition-shadow">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <i class="fa-solid fa-arrow-trend-up text-2xl text-green-600"></i>
                            </div>
                            <h3 class="font-semibold text-slate-800 mb-1">Kas Masuk</h3>
                            <p class="text-xs text-slate-500">Pencatatan pemasukan</p>
                        </div>

                        <div class="bg-white border border-slate-200 rounded-xl p-4 hover:shadow-md transition-shadow">
                            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <i class="fa-solid fa-arrow-trend-down text-2xl text-red-600"></i>
                            </div>
                            <h3 class="font-semibold text-slate-800 mb-1">Kas Keluar</h3>
                            <p class="text-xs text-slate-500">Pencatatan pengeluaran</p>
                        </div>

                        <div class="bg-white border border-slate-200 rounded-xl p-4 hover:shadow-md transition-shadow">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <i class="fa-solid fa-chart-line text-2xl text-blue-600"></i>
                            </div>
                            <h3 class="font-semibold text-slate-800 mb-1">Laporan</h3>
                            <p class="text-xs text-slate-500">Analisis keuangan</p>
                        </div>
                    </div>

                    {{-- Info Box --}}
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mt-8">
                        <div class="flex items-start gap-3 text-left">
                            <i class="fa-solid fa-info-circle text-blue-600 text-xl mt-0.5"></i>
                            <div>
                                <p class="text-sm text-blue-800 font-medium mb-1">Informasi</p>
                                <p class="text-sm text-blue-700 leading-relaxed">
                                    Saat ini Anda masih dapat menggunakan fitur-fitur lain dalam sistem. 
                                    Kami akan memberitahu Anda segera setelah fitur ini siap digunakan.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Back Button --}}
                    <div class="pt-4">
                        <a href="{{ route('dashboard') }}" 
                            class="inline-flex items-center gap-2 px-6 py-3 bg-[#344579] hover:bg-[#2e3e6a] text-white rounded-lg shadow transition-all hover:shadow-md">
                            <i class="fa-solid fa-arrow-left"></i>
                            <span>Kembali ke Dashboard</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Additional Info --}}
            <div class="text-center mt-6">
                <p class="text-sm text-slate-500">
                    <i class="fa-solid fa-clock mr-1"></i>
                    Target rilis: <span class="font-medium text-slate-700">Q1 2025</span>
                </p>
            </div>

        </div>
    </div>
@endsection