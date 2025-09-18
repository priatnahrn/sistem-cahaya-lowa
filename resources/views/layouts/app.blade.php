{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — CV Cahaya Lowa</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    @stack('styles')
</head>

<body class="bg-[#F9FAFB]">

    <!-- Shell -->
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        @include('components.sidebar')

        {{-- Main --}}
        <main class="flex-1 min-w-0 flex flex-col">
            {{-- Top header --}}
            <header class="h-[56px] flex items-center justify-between bg-white border-b border-slate-200 px-6">
                {{-- Left: Title --}}
                <h1 class="text-lg font-semibold text-slate-800">@yield('title')</h1>

                {{-- Right: User Info --}}
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <div class="text-sm font-semibold text-slate-700">{{ auth()->user()->name ?? 'Super Admin' }}
                        </div>
                        <div class="text-xs text-slate-500">{{ auth()->user()->role ?? 'Administrator' }}</div>
                    </div>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open=!open" class="flex items-center gap-2">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'SA') }}"
                                alt="avatar" class="w-9 h-9 rounded-full border">
                            <i class="fa-solid fa-chevron-down text-slate-500 text-xs"></i>
                        </button>
                        <div x-show="open" @click.outside="open=false" x-transition
                            class="absolute right-0 mt-2 w-48 bg-white border border-slate-200 rounded-lg shadow-lg overflow-hidden">
                            <a href="#" class="block px-4 py-2 text-sm hover:bg-slate-50">Profil</a>
                            <form method="POST" action="#">
                                @csrf
                                <button
                                    class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-slate-50">Keluar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>



            {{-- Content area (scroll) --}}
            <section class="flex-1 overflow-auto p-6">
                @yield('content')
            </section>
        </main>
    </div>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('scripts')
</body>

</html>
