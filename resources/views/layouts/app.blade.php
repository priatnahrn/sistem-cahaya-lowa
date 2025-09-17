<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
</head>
<body class="flex bg-[#F9FAFB] min-h-screen">

    {{-- Sidebar Komponen --}}
    <x-sidebar />

    {{-- Konten --}}
    <main class="flex-1 p-8">
        @yield('content')
    </main>
</body>
</html>
