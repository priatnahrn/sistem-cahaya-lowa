@extends('layouts.app')

@section('title', 'Halaman Tidak Ditemukan')

@section('content')
<section class="min-h-[70vh] flex flex-col items-center justify-center px-6">
    <div class="text-center">
        <h1 class="text-9xl font-extrabold text-[#4BAC87] tracking-widest">404</h1>
        <p class="text-2xl md:text-3xl font-semibold mt-4">Halaman Tidak Ditemukan</p>
        <p class="text-gray-500 mt-2">Maaf, halaman yang Anda cari tidak tersedia atau sudah dipindahkan.</p>

        <div class="mt-6 flex items-center justify-center gap-3">
            <a href="{{ url()->previous() }}"
               class="px-5 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200">
               Kembali
            </a>
            <a href="{{ url('/') }}"
               class="px-5 py-2.5 rounded-xl text-white bg-[#4BAC87] hover:bg-[#3a8f70] shadow">
               Ke Beranda
            </a>
        </div>
    </div>
</section>
@endsection
