<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'TIMSAR' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .leaflet-container { font-family: inherit; }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3">
            <a href="{{ route('public.report') }}" class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-red-600 font-black text-white">TS</span>
                <span>
                    <span class="block text-lg font-black leading-tight">TIMSAR NTB</span>
                    <span class="block text-xs font-semibold text-slate-500">Pelaporan dan koordinasi darurat</span>
                </span>
            </a>
            <nav class="flex items-center gap-2 text-sm font-bold">
                @auth
                    @if(auth()->user()->isAdmin())
                        <a class="hidden rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100 sm:block" href="{{ route('admin.dashboard') }}">Admin</a>
                    @else
                        <a class="hidden rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100 sm:block" href="{{ route('member.dashboard') }}">Anggota</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-lg bg-slate-900 px-3 py-2 text-white">Keluar</button>
                    </form>
                @else
                    <a class="rounded-lg bg-slate-900 px-3 py-2 text-white" href="{{ route('login') }}">Login Posko</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6">
        @if(session('status'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('status') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        {{ $slot }}
    </main>

    @stack('scripts')
</body>
</html>
