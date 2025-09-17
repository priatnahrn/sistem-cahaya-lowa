{{-- resources/views/auth/masuk.blade.php --}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login | CV Cahaya Lowa</title>

    @vite('resources/css/app.css')

    {{-- Font Awesome 6 (CDN) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        referrerpolicy="no-referrer" />
</head>

<body class="min-h-screen bg-[#EDF7F3] flex items-center justify-center">

    {{-- Toasts --}}
    <div x-data class="fixed top-4 right-4 space-y-2 z-50">
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition
                class="rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3 shadow">
                <div class="flex items-start gap-3">
                    <div class="font-semibold">Berhasil</div>
                    <div class="text-sm">{{ session('success') }}</div>
                    <button class="ml-auto" @click="show=false" aria-label="Tutup notifikasi">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-transition
                class="flex items-start gap-3 rounded-md border px-4 py-3 shadow text-sm"
                style="background-color:#FFEAE6; border-color:#F4522E; color:#F4522E;">
                {{-- Icon --}}
                <i class="fa-solid fa-circle-xmark text-lg mt-0.5"></i>

                {{-- Konten --}}
                <div>
                    <div class="font-semibold">Gagal</div>
                    <div>{{ session('error') }}</div>
                </div>

                {{-- Tombol close --}}
                <button class="ml-auto" @click="show=false" aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        @endif


        @if ($errors->any() && !$errors->has('username') && !$errors->has('password'))
            <div x-data="{ show: true }" x-show="show" x-transition
                class="rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 shadow">
                <div class="flex items-start gap-3">
                    <div class="font-semibold">Periksa inputan</div>
                    <div class="text-sm">{{ $errors->first() }}</div>
                    <button class="ml-auto" @click="show=false" aria-label="Tutup notifikasi">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        @endif
    </div>

    <main
        class="bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col lg:flex-row
                 w-full max-w-5xl min-h-[560px] mx-4 sm:mx-8 md:mx-12 lg:mx-20 my-8 lg:my-20">

        <style>
            .form-group {
                position: relative;
            }

            .form-label {
                position: absolute;
                left: 0;
                top: 50%;
                transform: translateY(-50%);
                color: #9ca3af;
                pointer-events: none;
                transition: all 0.2s ease;
            }

            input:focus~.form-label,
            input:not(:placeholder-shown)~.form-label {
                top: -8px;
                font-size: 0.75rem;
                color: #4BAC87;
            }
        </style>

        <section class="grid md:grid-cols-2 min-h-[calc(100vh-8rem)] w-full">
            {{-- KIRI / Ilustrasi --}}
            <div class="relative bg-[#4BAC87] text-white flex items-center justify-center overflow-hidden">
                <div
                    class="absolute -bottom-32 -right-28 w-[480px] h-[480px] bg-white/10 rounded-full blur-2xl pointer-events-none">
                </div>
                <div class="relative p-10">
                    <img src="{{ Vite::asset('resources/images/ilustrasi-1.jpg') }}" alt="Ilustrasi" class="mx-auto">
                    <p class="mt-6 text-center text-xs tracking-[0.25em] uppercase">
                        SISTEM MANAJEMEN TOKO CAHAYA LOWA
                    </p>
                </div>
            </div>

            {{-- KANAN / Form --}}
            <div class="p-8 md:p-12 flex flex-col justify-center">
                <div class="mb-6">
                    <h1 class="text-4xl font-bold text-[#4BAC87] mb-2">Masuk</h1>
                    <p class="text-[#5B5F6D]">Masukkan username dan password untuk masuk ke akun Anda.</p>
                </div>

                <form method="POST" action="{{ route('login') }}" class="space-y-7">
                    @csrf

                    {{-- Username --}}
                    <div class="form-group">
                        <input type="text" name="username" placeholder=" " value="{{ old('username') }}"
                            class="peer w-full bg-transparent border-0 border-b-2 border-gray-300 focus:border-[#4BAC87] outline-none py-3 text-gray-800" />
                        <label class="form-label">Username</label>
                        @error('username')
                            <p class="mt-2 text-sm" style="color:#F4522E;">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="form-group" x-data="{ show: false }">
                        <input :type="show ? 'text' : 'password'" id="password" name="password" placeholder=" "
                            class="peer w-full bg-transparent border-0 border-b-2 border-gray-300 focus:border-[#4BAC87] outline-none py-3 text-gray-800 pr-12" />
                        <label class="form-label">Password</label>

                        <button type="button" @click="show=!show"
                            class="absolute right-0 top-1/2 -translate-y-1/2 text-gray-500 hover:text-[#4BAC87] px-2 py-1">
                            <i x-show="!show" class="fa-regular fa-eye"></i>
                            <i x-show="show" class="fa-regular fa-eye-slash"></i>
                        </button>

                        @error('password')
                            <p class="mt-2 text-sm" style="color:#F4522E;">{{ $message }}</p>
                        @enderror
                    </div>


                    {{-- Submit --}}
                    <button type="submit"
                        class="w-full rounded-md bg-[#4BAC87] text-white font-semibold px-10 py-3 shadow-lg hover:shadow-xl transition">
                        Masuk
                    </button>
                </form>
            </div>
        </section>
    </main>

    {{-- AlpineJS (kalau belum dipaketkan di app.js) --}}
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>

</html>
