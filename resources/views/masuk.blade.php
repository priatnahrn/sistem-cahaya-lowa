{{-- resources/views/auth/masuk.blade.php (VERSI TAILWINDCSS — TEKSTUR DEKORATIF DI KOLOM CAHAYA LOWA) --}}
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

    <style>
        /* Preserve brand colors and small helpers */
        :root {
            --primary: #344579;
            --primary-2: #40548f;
            --error: #F4522E
        }

        /* tiny noise texture using data URI to add subtle grain (keeps file self-contained) */
        .noise-pattern {
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='200' height='200'><filter id='g'><feTurbulence baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/><feColorMatrix type='saturate' values='0'/></filter><rect width='100%' height='100%' filter='url(%23g)' opacity='0.03' fill='black'/></svg>");
            background-repeat: repeat;
        }

        /* Subtle paper / embossed texture for the brand block (keeps contrast low) */
        .paper-emboss {
            background-image: linear-gradient(180deg, rgba(255, 255, 255, 0.03), rgba(255, 255, 255, 0.01));
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04), inset 0 -8px 24px rgba(0, 0, 0, 0.03);
        }

        /* ensure SVG ornaments keep crispness on high-dpi */
        .ornament-svg {
            image-rendering: optimizeQuality;
        }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-b from-[#f3f6fb] to-[#eef3f8] flex items-center justify-center font-sans">
    {{-- Decorative background SVG (subtle) --}}
    <div class="fixed inset-0 pointer-events-none -z-10">
        <svg class="w-full h-full" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="g1" x1="0" x2="1">
                    <stop offset="0" stop-color="#eef2ff" />
                    <stop offset="1" stop-color="#f7fafc" />
                </linearGradient>
            </defs>
            <rect width="100%" height="100%" fill="url(#g1)" />
            <g opacity="0.05" fill="#344579">
                <ellipse cx="85%" cy="15%" rx="300" ry="120" />
                <ellipse cx="10%" cy="85%" rx="200" ry="90" />
            </g>
        </svg>
    </div>

    {{-- Toasts --}}
    <div x-data class="fixed top-6 right-6 space-y-3 z-50 w-80">
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition
                class="flex items-start gap-3 rounded-md border px-4 py-3 shadow text-sm"
                style="background-color:#ECFDF5; border-color:#A7F3D0; color:#065F46;">
                <i class="fa-solid fa-circle-check text-lg mt-0.5"></i>
                <div>
                    <div class="font-semibold">Berhasil</div>
                    <div>{{ session('success') }}</div>
                </div>
                <button class="ml-auto" @click="show=false" aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-transition
                class="flex items-start gap-3 rounded-md border px-4 py-3 shadow text-sm"
                style="background-color:#FFEAE6; border-color:#FCA5A5; color:#B91C1C;">
                <i class="fa-solid fa-circle-xmark text-lg mt-0.5"></i>
                <div>
                    <div class="font-semibold">Gagal</div>
                    <div>{{ session('error') }}</div>
                </div>
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

    <main class="w-full max-w-6xl mx-4 md:mx-6 lg:mx-8">
        <div
            class="bg-white/90 backdrop-blur rounded-2xl shadow-lg border border-slate-100 overflow-hidden md:flex md:items-stretch noise-pattern">

            {{-- Left / Brand panel (dengan ornamen & tekstur terfokus di area brand) --}}
            <aside class="hidden md:block md:w-2/5 lg:w-[42%] p-10 text-white relative paper-emboss"
                style="background: linear-gradient(135deg,var(--primary),var(--primary-2));">
                <div class="max-w-md relative z-20">
                    <div class="relative inline-block mb-3">
                        <!-- decorative textured badge behind the logo -->
                        <div class="absolute -inset-3 rounded-xl transform rotate-3 pointer-events-none opacity-60"
                            aria-hidden="true">
                            <!-- layered SVG texture: halftone + faint rings -->
                            <svg class="w-full h-full ornament-svg" viewBox="0 0 120 120"
                                xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                                <defs>
                                    <pattern id="halftone" x="0" y="0" width="6" height="6"
                                        patternUnits="userSpaceOnUse">
                                        <circle cx="1" cy="1" r="0.8" fill="white" opacity="0.06" />
                                    </pattern>
                                    <radialGradient id="rings" cx="30%" cy="30%" r="60%">
                                        <stop offset="0%" stop-color="white" stop-opacity="0.06" />
                                        <stop offset="100%" stop-color="white" stop-opacity="0" />
                                    </radialGradient>
                                </defs>
                                <rect width="120" height="120" fill="url(#halftone)" />
                                <circle cx="70" cy="30" r="34" fill="url(#rings)" />
                            </svg>
                        </div>

                        <div class="w-14 h-14 rounded-lg bg-white/6 flex items-center justify-center relative z-10">
                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" fill="white" opacity="0.06" />
                                <path d="M6 12c0-3 2.5-5 6-5s6 2 6 5-2.5 5-6 5-6-2-6-5z" stroke="white"
                                    stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                    </div>

                    <h2 class="text-2xl font-bold">CV Cahaya Lowa</h2>
                    <div class="text-sm text-white/90 mt-1">Sistem Manajemen Toko — Dashboard</div>

                    <p class="mt-5 text-sm text-white/90 leading-relaxed">Masuk untuk mengelola stok, transaksi, dan
                        laporan. Akses cepat, aman, dan responsif.</p>

                    <div class="grid grid-cols-2 gap-3 mt-5 text-sm text-white/95">
                        <div class="flex items-start gap-3">
                            <div class="w-7 h-7 rounded-md bg-white/5 flex items-center justify-center">
                                <i class="fa-solid fa-lock"></i>
                            </div>
                            <div>
                                <div class="font-semibold">Keamanan</div>
                                <div class="text-xs text-white/80">Enkripsi &amp; hak akses</div>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-7 h-7 rounded-md bg-white/5 flex items-center justify-center">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                            <div>
                                <div class="font-semibold">Laporan</div>
                                <div class="text-xs text-white/80">Ringkas &amp; real-time</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ORNAMENTS: SVG shapes + subtle pattern overlay (posisi absolute agar tidak memengaruhi layout) -->
                <div class="absolute inset-0 pointer-events-none z-10">
                    <!-- radial soft glow -->
                    <svg class="absolute -right-10 -top-10 opacity-20" width="260" height="260"
                        viewBox="0 0 260 260" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <radialGradient id="rg1" cx="0.3" cy="0.3">
                                <stop offset="0" stop-color="#ffffff" stop-opacity="0.06"></stop>
                                <stop offset="1" stop-color="#ffffff" stop-opacity="0"></stop>
                            </radialGradient>
                        </defs>
                        <rect width="260" height="260" fill="url(#rg1)" transform="rotate(12 0 0)" />
                    </svg>

                    <!-- translucent diagonal stripes -->
                    <svg class="absolute left-6 bottom-6 opacity-8" width="220" height="120"
                        viewBox="0 0 220 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="p1" width="20" height="20" patternUnits="userSpaceOnUse"
                                patternTransform="rotate(25)">
                                <rect width="10" height="20" fill="rgba(255,255,255,0.02)"></rect>
                            </pattern>
                        </defs>
                        <rect width="220" height="120" fill="url(#p1)"></rect>
                    </svg>

                    <!-- small decorative circles -->
                    <svg class="absolute -left-10 -bottom-8 opacity-30" width="140" height="140"
                        viewBox="0 0 140 140" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="30" cy="30" r="28" fill="white" opacity="0.02" />
                        <circle cx="110" cy="90" r="36" fill="white" opacity="0.03" />
                    </svg>

                    <!-- subtle grid dots -->
                    <svg class="absolute right-6 bottom-20 opacity-10" width="120" height="120"
                        viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="dots" width="12" height="12" patternUnits="userSpaceOnUse">
                                <circle cx="1" cy="1" r="1" fill="white" opacity="0.06" />
                            </pattern>
                        </defs>
                        <rect width="120" height="120" fill="url(#dots)" />
                    </svg>
                </div>

                <!-- Slight glass overlay to preserve depth -->
                <div
                    class="absolute inset-0 bg-gradient-to-b from-transparent to-black/2 opacity-5 pointer-events-none z-0">
                </div>
            </aside>

            {{-- Right / Form panel --}}
            <section class="w-full md:w-3/5 lg:w-[58%] p-8 md:p-12 flex items-center justify-center">
                <div class="w-full max-w-md">
                    <h1 class="text-2xl md:text-3xl font-bold text-slate-900">Masuk</h1>
                    <p class="text-sm text-slate-600 mt-2">Masukkan <strong>username</strong> dan
                        <strong>password</strong> Anda untuk melanjutkan.</p>

                    <form method="POST" action="{{ route('login') }}" class="space-y-5 mt-6" novalidate>
                        @csrf

                        {{-- Username --}}
                        <div>
                            <label for="username" class="sr-only">Username</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"><i
                                        class="fa-solid fa-user"></i></span>
                                <input id="username" name="username" type="text" placeholder="Username"
                                    value="{{ old('username') }}"
                                    class="w-full pl-12 pr-4 py-3 rounded-lg bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]"
                                    aria-invalid="{{ $errors->has('username') ? 'true' : 'false' }}"
                                    aria-describedby="username-error" />
                            </div>

                            @error('username')
                                <div id="username-error" class="mt-2 text-sm text-[var(--error)]">{{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div x-data="{ show: false }">
                            <label for="password" class="sr-only">Password</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"><i
                                        class="fa-solid fa-key"></i></span>
                                <input :type="show ? 'text' : 'password'" id="password" name="password"
                                    placeholder="Password"
                                    class="w-full pl-12 pr-12 py-3 rounded-lg bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]"
                                    aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                                    aria-describedby="password-error" />

                                <button type="button"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 p-2 rounded-md text-slate-500"
                                    @click="show = !show"
                                    :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'">
                                    <i x-show="!show" class="fa-regular fa-eye"></i>
                                    <i x-show="show" class="fa-regular fa-eye-slash"></i>
                                </button>
                            </div>

                            @error('password')
                                <div id="password-error" class="mt-2 text-sm text-[var(--error)]">{{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Remember & Forgot --}}
                        <div class="flex items-center justify-between">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300" />
                                <span>Ingat saya</span>
                            </label>

                            <a href="#" class="text-sm font-semibold text-[#344579]">Lupa kata sandi?</a>
                        </div>

                        {{-- Submit --}}
                        <div>
                            <button type="submit" class="w-full py-3 rounded-lg font-semibold text-white shadow-md"
                                style="background: linear-gradient(90deg,var(--primary),var(--primary-2));">Masuk</button>
                        </div>

                        {{-- Help --}}
                        <div class="text-center mt-2">
                            <div class="text-sm text-slate-500">Butuh bantuan? <a href="#"
                                    class="font-semibold text-[#344579]">Hubungi admin</a></div>
                        </div>

                        <div class="text-center mt-4 text-sm text-slate-400">© {{ date('Y') }} CV Cahaya Lowa —
                            Semua hak dilindungi.</div>
                    </form>
                </div>
            </section>
        </div>
    </main>

    {{-- AlpineJS (untuk toasts & password toggle) --}}
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>

</html>
