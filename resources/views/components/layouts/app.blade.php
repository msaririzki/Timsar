@props([
    'title' => 'TIMSAR',
    'hideChrome' => false,
    'fullBleed' => false,
])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .leaflet-container { font-family: inherit; background: #e2e8f0; }
        .leaflet-control-zoom { overflow: hidden; border: 1px solid #cbd5e1 !important; border-radius: 8px !important; box-shadow: 0 8px 24px rgba(15, 23, 42, .12) !important; }
        .leaflet-control-zoom a { color: #334155 !important; border-color: #e2e8f0 !important; }
        .leaflet-control-attribution { color: #64748b; background: rgba(255, 255, 255, .88) !important; }
        .leaflet-popup-content-wrapper { border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 14px 32px rgba(15, 23, 42, .16); }
        .leaflet-popup-content { margin: 10px 14px; color: #334155; line-height: 1.45; }
        .leaflet-popup-tip { box-shadow: none; }

        .timsar-map-marker { position: relative; width: 44px; height: 44px; }
        .timsar-map-marker__halo { position: absolute; inset: 5px; border-radius: 999px; background: var(--marker-color); opacity: .2; animation: timsar-marker-pulse 2s ease-out infinite; }
        .timsar-map-marker__body { position: absolute; left: 8px; top: 8px; display: grid; width: 28px; height: 28px; place-items: center; color: #fff; background: var(--marker-color); border: 3px solid #fff; box-shadow: 0 5px 14px rgba(15, 23, 42, .3); }
        .timsar-map-marker__symbol { display: block; font: 900 14px/1 ui-sans-serif, system-ui, sans-serif; }
        .timsar-map-marker--incident { --marker-color: #dc2626; }
        .timsar-map-marker--incident .timsar-map-marker__body { border-radius: 50% 50% 50% 12%; transform: rotate(-45deg); }
        .timsar-map-marker--incident .timsar-map-marker__symbol { transform: rotate(45deg); }
        .timsar-map-marker--member { --marker-color: #059669; }
        .timsar-map-marker--member .timsar-map-marker__body,
        .timsar-map-marker--user .timsar-map-marker__body { border-radius: 999px; }
        .timsar-map-marker--member .timsar-map-marker__symbol { transform: translateY(-1px); }
        .timsar-map-marker--user { --marker-color: #2563eb; }
        .timsar-map-marker--cell { --marker-color: #d97706; }
        .timsar-map-marker--cell .timsar-map-marker__body { border-radius: 999px; }
        .timsar-map-marker--offline { --marker-color: #64748b; }
        .timsar-map-marker--still .timsar-map-marker__halo { animation: none; opacity: .12; }

        .timsar-route-line { animation: timsar-route-flow 1.4s linear infinite; filter: drop-shadow(0 1px 2px rgba(220, 38, 38, .22)); }
        .timsar-trail-line { filter: drop-shadow(0 1px 2px rgba(37, 99, 235, .2)); }

        @keyframes timsar-marker-pulse {
            0% { transform: scale(.55); opacity: .4; }
            75%, 100% { transform: scale(1.35); opacity: 0; }
        }
        @keyframes timsar-route-flow { to { stroke-dashoffset: -24; } }
        @media (prefers-reduced-motion: reduce) {
            .timsar-map-marker__halo, .timsar-route-line { animation: none !important; }
        }
    </style>
    <script>
        window.TimsarMap = (() => {
            const markerAnimations = new WeakMap();

            function icon(type, options = {}) {
                const symbol = type === 'incident' ? '!' : (type === 'member' ? '&#9650;' : (type === 'cell' ? '&#8644;' : '&#9679;'));
                const stillClass = options.pulse === false ? ' timsar-map-marker--still' : '';
                const offlineClass = options.offline ? ' timsar-map-marker--offline' : '';

                return L.divIcon({
                    className: '',
                    html: `<div class="timsar-map-marker timsar-map-marker--${type}${stillClass}${offlineClass}" aria-hidden="true"><span class="timsar-map-marker__halo"></span><span class="timsar-map-marker__body"><span class="timsar-map-marker__symbol">${symbol}</span></span></div>`,
                    iconSize: [44, 44],
                    iconAnchor: [22, type === 'incident' ? 36 : 22],
                    popupAnchor: [0, type === 'incident' ? -34 : -22],
                });
            }

            function addTiles(map) {
                map.getContainer().classList.add('timsar-map');
                return L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    updateWhenIdle: true,
                    keepBuffer: 3,
                    attribution: '&copy; OpenStreetMap',
                }).addTo(map);
            }

            function routeOptions(overrides = {}) {
                return { color: '#dc2626', weight: 5, opacity: .92, dashArray: '12 8', lineCap: 'round', lineJoin: 'round', className: 'timsar-route-line', ...overrides };
            }

            function trailOptions(overrides = {}) {
                return { color: '#2563eb', weight: 5, opacity: .82, lineCap: 'round', lineJoin: 'round', className: 'timsar-trail-line', ...overrides };
            }

            function moveMarker(marker, point, duration = 700) {
                const target = L.latLng(point);
                const start = marker.getLatLng();
                const previousFrame = markerAnimations.get(marker);
                if (previousFrame) cancelAnimationFrame(previousFrame);

                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || start.distanceTo(target) > 5000) {
                    marker.setLatLng(target);
                    return;
                }

                const startedAt = performance.now();
                const animate = (now) => {
                    const progress = Math.min((now - startedAt) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    marker.setLatLng([
                        start.lat + ((target.lat - start.lat) * eased),
                        start.lng + ((target.lng - start.lng) * eased),
                    ]);
                    if (progress < 1) markerAnimations.set(marker, requestAnimationFrame(animate));
                    else markerAnimations.delete(marker);
                };
                markerAnimations.set(marker, requestAnimationFrame(animate));
            }

            return { icon, addTiles, routeOptions, trailOptions, moveMarker };
        })();

        window.TimsarNativeBridge = (() => {
            let latestCell = null;

            window.addEventListener('timsar:cell-info', (event) => {
                try {
                    latestCell = typeof event.detail === 'string' ? JSON.parse(event.detail) : event.detail;
                } catch (error) {
                    latestCell = null;
                }
            });

            return {
                cell: () => latestCell,
                available: () => latestCell !== null,
            };
        })();
    </script>
    @auth
        @if(auth()->user()->isMember())
            <script>
                window.TimsarNativeBackgroundActive = false;
                window.addEventListener('timsar:background-service', (event) => {
                    window.TimsarNativeBackgroundActive = event.detail?.active === true;
                });

                window.addEventListener('load', () => {
                    window.TimsarNative?.postMessage(JSON.stringify({
                        action: 'syncBackgroundService',
                        origin: window.location.origin,
                        csrf: document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        activeUrl: @json(route('member.active-assignment')),
                        locationUrl: @json(route('member.location.update')),
                        heartbeatUrl: @json(route('member.heartbeat')),
                    }));
                });

                document.addEventListener('submit', (event) => {
                    if (!event.target?.action?.endsWith('/logout')) return;
                    window.TimsarNative?.postMessage(JSON.stringify({ action: 'stopBackgroundService' }));
                });
            </script>
        @endif
    @endauth
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    @unless($hideChrome)
    @php
        $isMemberShell = auth()->check() && auth()->user()->isMember();
    @endphp

    @if($isMemberShell)
    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 shadow-sm backdrop-blur-xl">
        <div class="mx-auto flex h-14 max-w-7xl items-center justify-between gap-3 px-3 sm:px-4">
            <a href="{{ route('member.dashboard') }}" class="flex min-w-0 items-center gap-2">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-slate-950 text-sm font-black text-white shadow-sm">TS</span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-black leading-tight text-slate-950">TIMSAR</span>
                    <span class="block truncate text-[11px] font-bold leading-tight text-slate-500">Panel anggota</span>
                </span>
            </a>
            <nav class="flex shrink-0 items-center gap-1.5 text-xs font-black">
                <a class="inline-flex h-9 items-center rounded-lg px-3 {{ request()->routeIs('member.dashboard') ? 'bg-slate-950 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}" href="{{ route('member.dashboard') }}">
                    Dashboard
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-slate-700 shadow-sm hover:bg-slate-50">Keluar</button>
                </form>
            </nav>
        </div>
    </header>
    @else
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
        @php
            $homeRoute = auth()->check()
                ? (auth()->user()->isAdmin() ? route('admin.dashboard') : route('member.dashboard'))
                : route('public.report');
        @endphp
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3">
            <a href="{{ $homeRoute }}" class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-red-600 font-black text-white">TS</span>
                <span>
                    <span class="block text-lg font-black leading-tight">TIMSAR NTB</span>
                    <span class="block text-xs font-semibold text-slate-500">Pelaporan dan koordinasi darurat</span>
                </span>
            </a>
            <nav class="flex items-center gap-2 text-sm font-bold">
                @auth
                    @if(auth()->user()->isAdmin())
                        <a class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100" href="{{ route('admin.dashboard') }}">Admin</a>
                    @else
                        <a class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100" href="{{ route('member.dashboard') }}">Anggota</a>
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
    @endif
    @endunless

    <main class="{{ $fullBleed ? 'mx-auto max-w-none px-0 py-0' : 'mx-auto max-w-7xl px-4 py-6' }}">
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
