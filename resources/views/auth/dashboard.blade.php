@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1 class="text-2xl font-bold mb-6 text-slate-700">Dashboard</h1>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Total Penjualan</p>
                    <h2 class="text-2xl font-bold text-slate-700 mt-1">1,250</h2>
                </div>
                <div class="w-10 h-10 flex items-center justify-center rounded-lg bg-[#4BAC87]/10 text-[#4BAC87]">
                    <i class="fa-solid fa-coins"></i>
                </div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Total Pembelian</p>
                    <h2 class="text-2xl font-bold text-slate-700 mt-1">980</h2>
                </div>
                <div class="w-10 h-10 flex items-center justify-center rounded-lg bg-[#4BAC87]/10 text-[#4BAC87]">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Jumlah Supplier</p>
                    <h2 class="text-2xl font-bold text-slate-700 mt-1">35</h2>
                </div>
                <div class="w-10 h-10 flex items-center justify-center rounded-lg bg-[#4BAC87]/10 text-[#4BAC87]">
                    <i class="fa-solid fa-truck"></i>
                </div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Jumlah Pelanggan</p>
                    <h2 class="text-2xl font-bold text-slate-700 mt-1">420</h2>
                </div>
                <div class="w-10 h-10 flex items-center justify-center rounded-lg bg-[#4BAC87]/10 text-[#4BAC87]">
                    <i class="fa-solid fa-user-group"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Grafik & Table --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Grafik Dummy --}}
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm lg:col-span-2">
            <h3 class="text-lg font-semibold text-slate-700 mb-4">Grafik Penjualan</h3>
            <div class="h-64 flex items-center justify-center text-slate-400 border border-dashed border-slate-300 rounded-lg">
                <span>Grafik Placeholder</span>
            </div>
        </div>

        {{-- Table Dummy --}}
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-700 mb-4">Transaksi Terbaru</h3>
            <table class="w-full text-sm text-left text-slate-600">
                <thead>
                    <tr class="border-b border-slate-200 text-slate-500">
                        <th class="py-2">Tanggal</th>
                        <th class="py-2">No. Invoice</th>
                        <th class="py-2">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-slate-100">
                        <td class="py-2">17 Sep 2025</td>
                        <td class="py-2">INV-001</td>
                        <td class="py-2">Rp 2.500.000</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="py-2">16 Sep 2025</td>
                        <td class="py-2">INV-002</td>
                        <td class="py-2">Rp 1.800.000</td>
                    </tr>
                    <tr>
                        <td class="py-2">15 Sep 2025</td>
                        <td class="py-2">INV-003</td>
                        <td class="py-2">Rp 950.000</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
