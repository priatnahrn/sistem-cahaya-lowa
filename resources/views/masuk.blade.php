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

    <style>
        :root {
            --primary: #344579;
            --primary-2: #40548f;
            --muted: #6b7280;
            --error: #F4522E;
        }

        /* Page */
        body {
            min-height: 100vh;
            background: linear-gradient(180deg, #f3f6fb, #eef3f8);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue",
                Arial;
        }

        main.container {
            width: 100%;
            max-width: 1200px;
            margin: 2.5rem;
        }

        /* Card */
        .card {
            display: flex;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 16px 48px rgba(16, 24, 40, 0.08);
            border: 1px solid rgba(15, 23, 42, 0.04);
            background: linear-gradient(180deg, rgba(255,255,255,0.9), rgba(255,255,255,0.85));
        }

        /* Left / brand panel */
        .panel-left {
            flex: 1;
            min-width: 360px;
            max-width: 520px;
            padding: 44px 48px;
            color: white;
            background: linear-gradient(135deg, var(--primary), var(--primary-2));
            position: relative;
            display: flex;
            align-items: center;
        }

        .panel-inner {
            width: 100%;
            max-width: 420px;
        }

        .logo-mark {
            display: inline-grid;
            place-items: center;
            width: 56px;
            height: 56px;
            border-radius: 12px;
            background: rgba(255,255,255,0.06);
            margin-bottom: 12px;
        }

        .brand-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .brand-sub {
            font-size: 0.86rem;
            color: rgba(255,255,255,0.9);
            margin-top: 4px;
        }

        .lead {
            margin-top: 18px;
            color: rgba(255,255,255,0.88);
            font-size: 0.96rem;
            line-height: 1.6;
        }

        .features {
            margin-top: 18px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            font-size: 0.88rem;
            color: rgba(255,255,255,0.92);
        }

        .feature {
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .feature .ico {
            width: 28px;
            height: 28px;
            display: grid;
            place-items: center;
            border-radius: 8px;
            background: rgba(255,255,255,0.04);
        }

        .panel-left::after {
            content: "";
            position: absolute;
            right: -40px;
            top: -40px;
            width: 220px;
            height: 220px;
            transform: rotate(18deg);
            background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.02), transparent 40%);
            pointer-events: none;
        }

        /* Right / form panel */
        .panel-right {
            flex: 1;
            padding: 44px 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-wrap {
            width: 100%;
            max-width: 460px;
        }

        .title {
            font-size: 1.875rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 6px 0;
        }

        .subtitle {
            font-size: 0.95rem;
            color: #475569;
            margin-bottom: 20px;
        }

        /* Input styles (static placeholder) */
        .input-with-icon {
            position: relative;
        }

        .input-with-icon .icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 1rem;
            pointer-events: none;
        }

        .underline-field {
            width: 100%;
            padding: 12px 44px 12px 44px; /* space for icon and optional eye */
            border: none;
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.03);
            outline: none;
            font-size: 0.98rem;
            color: #0f172a;
            transition: box-shadow .12s ease, transform .08s ease;
        }

        .underline-field::placeholder { color: #94a3b8; }

        .underline-field:focus {
            box-shadow: 0 10px 26px rgba(52, 69, 121, 0.06);
            transform: translateY(-1px);
            border: 1px solid rgba(52, 69, 121, 0.06);
        }

        .input-eye {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            color: var(--muted);
            padding: 6px;
            border-radius: 6px;
            font-size: 1rem;
        }

        .row-between {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .muted-note {
            color: #64748b;
            font-size: 0.9rem;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            font-weight: 700;
            background: linear-gradient(90deg, var(--primary), var(--primary-2));
            color: white;
            box-shadow: 0 12px 30px rgba(52,69,121,0.12);
            border: none;
            cursor: pointer;
            margin-top: 8px;
        }

        .error-text {
            color: var(--error);
            font-size: 0.9rem;
            margin-top: 8px;
        }

        /* Toast container small screen adjust */
        @media (max-width: 880px) {
            main.container { margin: 16px; }
            .card { flex-direction: column; }
            .panel-left, .panel-right { padding: 28px; }
        }
    </style>
</head>

<body>
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
    <div x-data class="fixed top-6 right-6 space-y-2 z-50 w-80">
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

    <main class="container">
        <div class="card">
            {{-- Left panel --}}
            <div class="panel-left">
                <div class="panel-inner">
                    <div class="logo-mark" aria-hidden="true">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" fill="white" opacity="0.06" />
                            <path d="M6 12c0-3 2.5-5 6-5s6 2 6 5-2.5 5-6 5-6-2-6-5z" stroke="white"
                                stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>

                    <h2 class="brand-title">CV Cahaya Lowa</h2>
                    <div class="brand-sub">Sistem Manajemen Toko — Dashboard</div>

                    <p class="lead">
                        Masuk untuk mengelola stok, transaksi, dan laporan. Akses cepat, aman, dan responsif.
                    </p>

                    <div class="features" aria-hidden="true">
                        <div class="feature">
                            <div class="ico"><i class="fa-solid fa-lock"></i></div>
                            <div>
                                <div style="font-weight:600">Keamanan</div>
                                <div style="font-size:0.82rem; color: rgba(255,255,255,0.85)">Enkripsi &amp; hak akses</div>
                            </div>
                        </div>

                        <div class="feature">
                            <div class="ico"><i class="fa-solid fa-chart-line"></i></div>
                            <div>
                                <div style="font-weight:600">Laporan</div>
                                <div style="font-size:0.82rem; color: rgba(255,255,255,0.85)">Ringkas &amp; real-time</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right panel (form) --}}
            <div class="panel-right">
                <div class="form-wrap">
                    <h1 class="title">Masuk</h1>
                    <p class="subtitle">Masukkan <strong>username</strong> dan <strong>password</strong> Anda untuk
                        melanjutkan.</p>

                    <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate>
                        @csrf

                        {{-- Username --}}
                        <div>
                            <label for="username" class="sr-only">Username</label>
                            <div class="input-with-icon">
                                <span class="icon"><i class="fa-solid fa-user"></i></span>
                                <input id="username" name="username" type="text" placeholder="Username"
                                    value="{{ old('username') }}" class="underline-field"
                                    aria-invalid="{{ $errors->has('username') ? 'true' : 'false' }}"
                                    aria-describedby="username-error" />
                            </div>

                            @error('username')
                                <div id="username-error" class="error-text">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div x-data="{ show: false }">
                            <label for="password" class="sr-only">Password</label>
                            <div class="input-with-icon">
                                <span class="icon"><i class="fa-solid fa-key"></i></span>
                                <input :type="show ? 'text' : 'password'" id="password" name="password"
                                    placeholder="Password" class="underline-field"
                                    aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                                    aria-describedby="password-error" />
                                <button type="button" class="input-eye" @click="show = !show"
                                    :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'">
                                    <i x-show="!show" class="fa-regular fa-eye"></i>
                                    <i x-show="show" class="fa-regular fa-eye-slash"></i>
                                </button>
                            </div>

                            @error('password')
                                <div id="password-error" class="error-text">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Remember & Forgot --}}
                        <div class="row-between">
                            <label class="inline-flex items-center gap-2 muted-note">
                                <input type="checkbox" name="remember" class="rounded border-gray-300" />
                                <span style="font-size:0.92rem; color:#475569;">Ingat saya</span>
                            </label>

                            <a href="#" style="color: var(--primary); font-weight:600; font-size:0.92rem;">Lupa kata
                                sandi?</a>
                        </div>

                        {{-- Submit --}}
                        <div>
                            <button type="submit" class="btn-submit">Masuk</button>
                        </div>

                        {{-- Help --}}
                        <div style="text-align:center; margin-top:8px;">
                            <div class="muted-note">Butuh bantuan? <a href="#"
                                    style="color:var(--primary); font-weight:600">Hubungi admin</a></div>
                        </div>

                        <div style="text-align:center; margin-top:12px; color:#94a3b8; font-size:0.82rem;">
                            © {{ date('Y') }} CV Cahaya Lowa — Semua hak dilindungi.
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    {{-- AlpineJS (untuk toasts & password toggle) --}}
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>

</html>
