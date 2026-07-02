@php
    $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . $assignment->report->latitude . ',' . $assignment->report->longitude;
    $directionsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . $assignment->report->latitude . ',' . $assignment->report->longitude;
    $reporterPhone = 'tel:' . preg_replace('/[^\d+]/', '', $assignment->report->reporter_phone);
    $assignmentClosed = in_array($assignment->status, ['completed', 'cancelled'], true);
    $navigationMode = $assignment->status === 'on_the_way';
@endphp

<x-layouts.app title="Mode Tugas TIMSAR" :hide-chrome="$navigationMode" :full-bleed="$navigationMode">

    <section class="{{ $navigationMode ? 'space-y-2 pb-16' : 'space-y-4' }}">
        @if($navigationMode)
            <div class="border-b border-red-100 bg-white px-3 py-2 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-red-600 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-white">OTW</span>
                            <span id="assignmentStatusText" class="truncate text-xs font-black uppercase text-red-700">{{ \App\Http\Controllers\PublicTrackingController::assignmentLabel($assignment->status) }}</span>
                        </div>
                        <h1 class="mt-0.5 truncate text-base font-black leading-tight text-slate-950">{{ $assignment->report->incident_type }}</h1>
                        <p class="truncate text-xs font-semibold text-slate-500">{{ $assignment->report->tracking_code }} - {{ $assignment->report->reporter_name }}</p>
                    </div>
                    <a href="{{ route('member.dashboard') }}" class="shrink-0 rounded-lg bg-slate-900 px-3 py-2 text-xs font-black text-white">
                        Dashboard
                    </a>
                </div>

                <div class="mt-2 grid grid-cols-3 gap-1.5 text-center">
                    <div class="rounded-lg bg-slate-50 px-2 py-1.5">
                        <p class="text-[10px] font-black uppercase text-slate-500">Jarak</p>
                        <p id="distanceText" class="mt-0.5 truncate text-sm font-black">{{ $assignment->distance_meters ? number_format($assignment->distance_meters / 1000, 2) . ' km' : '-' }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 px-2 py-1.5">
                        <p class="text-[10px] font-black uppercase text-slate-500">ETA</p>
                        <p id="durationText" class="mt-0.5 truncate text-sm font-black">{{ $assignment->duration_seconds ? round($assignment->duration_seconds / 60) . ' menit' : '-' }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 px-2 py-1.5">
                        <p class="text-[10px] font-black uppercase text-slate-500">GPS</p>
                        <p id="gpsStatus" class="mt-0.5 truncate text-sm font-black">Mengaktifkan...</p>
                    </div>
                </div>
            </div>
        @else
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full {{ $assignmentClosed ? 'bg-slate-700' : 'bg-red-600' }} px-3 py-1 text-xs font-black uppercase tracking-wide text-white">
                            {{ $assignmentClosed ? 'Tugas ditutup' : 'Sedang bertugas' }}
                        </span>
                        <span class="rounded-full {{ $assignmentClosed ? 'bg-slate-100 text-slate-700' : 'bg-red-50 text-red-700' }} px-3 py-1 text-xs font-black uppercase tracking-wide">{{ \App\Http\Controllers\PublicTrackingController::assignmentLabel($assignment->status) }}</span>
                    </div>
                    <p class="mt-3 text-xs font-black uppercase text-red-600">{{ $assignment->report->tracking_code }}</p>
                    <h1 class="mt-1 text-2xl font-black leading-tight md:text-3xl">{{ $assignment->report->incident_type }}</h1>
                    <p class="mt-2 text-sm font-semibold text-slate-600">{{ $assignment->report->description }}</p>
                </div>
                <a href="{{ route('member.dashboard') }}" class="rounded-xl bg-slate-900 px-4 py-3 text-center text-sm font-black text-white">
                    Dashboard
                </a>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-2 md:grid-cols-4">
                <div class="rounded-xl bg-slate-50 p-3">
                    <p class="text-[11px] font-black uppercase text-slate-500">Status</p>
                    <p id="assignmentStatusText" class="mt-1 font-black">{{ \App\Http\Controllers\PublicTrackingController::assignmentLabel($assignment->status) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-3">
                    <p class="text-[11px] font-black uppercase text-slate-500">Jarak</p>
                    <p id="distanceText" class="mt-1 font-black">{{ $assignment->distance_meters ? number_format($assignment->distance_meters / 1000, 2) . ' km' : '-' }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-3">
                    <p class="text-[11px] font-black uppercase text-slate-500">Estimasi</p>
                    <p id="durationText" class="mt-1 font-black">{{ $assignment->duration_seconds ? round($assignment->duration_seconds / 60) . ' menit' : '-' }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-3">
                    <p class="text-[11px] font-black uppercase text-slate-500">GPS saya</p>
                    <p id="gpsStatus" class="mt-1 font-black">Mengaktifkan...</p>
                </div>
            </div>
        </div>
        @endif

        <div class="{{ $navigationMode ? 'overflow-hidden bg-white shadow-sm' : 'overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm' }}">
            <div class="relative">
                <div id="assignmentMap" class="{{ $navigationMode ? 'h-[calc(100dvh-176px)] min-h-[620px] md:h-[calc(100vh-138px)] md:min-h-[720px]' : 'h-[62vh] min-h-[430px] md:h-[680px]' }}"></div>

                <div class="pointer-events-none absolute left-3 right-3 top-3 z-[500] flex items-start {{ $navigationMode ? 'justify-end' : 'justify-between' }} gap-3">
                    @unless($navigationMode)
                    <div class="pointer-events-auto rounded-2xl bg-white/95 p-3 text-slate-900 shadow-lg backdrop-blur">
                        <p class="text-[11px] font-black uppercase text-slate-500">Navigasi tugas</p>
                        <p id="mapRouteMeta" class="mt-1 text-sm font-black">Menunggu GPS terbaik...</p>
                        <div class="mt-2 flex flex-wrap gap-2 text-[11px] font-black text-slate-600">
                            <span class="inline-flex items-center gap-1"><span class="h-1.5 w-5 rounded-full bg-blue-600"></span>Jalur ditempuh</span>
                            <span class="inline-flex items-center gap-1"><span class="h-1.5 w-5 rounded-full bg-red-500"></span>Rute tersisa</span>
                        </div>
                    </div>
                    @else
                        <p id="mapRouteMeta" class="hidden">Menunggu GPS terbaik...</p>
                    @endunless
                    <div class="pointer-events-auto grid gap-2">
                        <button id="focusMeButton" type="button" class="rounded-xl bg-white/95 px-3 py-2 text-sm font-black text-slate-900 shadow-lg">{{ $navigationMode ? 'Ikuti' : 'Saya' }}</button>
                        <button id="fitRouteButton" type="button" class="rounded-xl bg-white/95 px-3 py-2 text-sm font-black text-slate-900 shadow-lg">Rute</button>
                    </div>
                </div>

                @if($navigationMode)
                    <div id="routeDeviationNotice" class="pointer-events-none absolute left-3 top-3 z-[500] hidden max-w-[70%] rounded-xl bg-amber-500 px-3 py-2 text-xs font-black text-slate-950 shadow-lg">
                        Keluar jalur. Memperbarui rute...
                    </div>
                @else
                    <div id="routeDeviationNotice" class="hidden"></div>
                @endif

                <div class="pointer-events-none absolute bottom-3 left-3 right-3 z-[500]">
                    <div class="pointer-events-auto rounded-2xl bg-white/95 p-3 shadow-lg backdrop-blur">
                        <div class="grid {{ $navigationMode ? 'grid-cols-3' : 'grid-cols-2 sm:grid-cols-5' }} gap-2 text-center">
                            <div>
                                <p class="text-[11px] font-black uppercase text-slate-500">Akurasi</p>
                                <p id="accuracyValue" class="font-black">-</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-black uppercase text-slate-500">Terkirim</p>
                                <p id="lastSentValue" class="font-black">-</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-black uppercase text-slate-500">Jaringan</p>
                                <p id="networkStatus" class="font-black">-</p>
                            </div>
                            @unless($navigationMode)
                                <div>
                                    <p class="text-[11px] font-black uppercase text-slate-500">Ditempuh</p>
                                    <p id="trailDistanceValue" class="font-black">-</p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-black uppercase text-slate-500">BTS</p>
                                    <p id="cellStatusValue" class="truncate font-black">Web</p>
                                </div>
                            @else
                                <p id="trailDistanceValue" class="hidden">-</p>
                                <p id="cellStatusValue" class="hidden">Web</p>
                            @endunless
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @unless($assignmentClosed)
        <div class="sticky bottom-0 z-[700] {{ $navigationMode ? 'border-t border-slate-200 bg-white/95 px-3 py-2' : '-mx-4 border-t border-slate-200 bg-white/95 px-4 py-3' }} shadow-[0_-12px_30px_rgba(15,23,42,0.08)] backdrop-blur">
            <div class="mx-auto grid max-w-7xl {{ $navigationMode ? 'grid-cols-1' : 'grid-cols-2 gap-2 lg:grid-cols-4' }} gap-2">
                @if($assignment->status === 'assigned')
                    <form method="POST" action="{{ route('member.assignments.accept', $assignment) }}" class="col-span-2 lg:col-span-2">
                        @csrf
                        <button class="w-full rounded-xl bg-slate-900 px-4 py-4 font-black text-white">Terima</button>
                    </form>
                @elseif($assignment->status === 'accepted')
                    <form method="POST" action="{{ route('member.assignments.start', $assignment) }}" class="col-span-2 lg:col-span-2">
                        @csrf
                        <button class="w-full rounded-xl bg-blue-600 px-4 py-4 font-black text-white">Mulai jalan</button>
                    </form>
                @elseif($assignment->status === 'on_the_way')
                    <form method="POST" action="{{ route('member.assignments.arrive', $assignment) }}" class="{{ $navigationMode ? '' : 'col-span-2 lg:col-span-2' }}">
                        @csrf
                        <button class="w-full rounded-xl bg-amber-500 px-4 py-3 font-black text-white">Sampai</button>
                    </form>
                @elseif($assignment->status === 'arrived')
                    <form method="POST" action="{{ route('member.assignments.handling', $assignment) }}" class="col-span-2 lg:col-span-2">
                        @csrf
                        <button class="w-full rounded-xl bg-purple-600 px-4 py-4 font-black text-white">Tangani</button>
                    </form>
                @elseif($assignment->status === 'handling')
                    <form method="POST" action="{{ route('member.assignments.complete', $assignment) }}" class="col-span-2 lg:col-span-2">
                        @csrf
                        <button class="w-full rounded-xl bg-emerald-600 px-4 py-4 font-black text-white">Selesai</button>
                    </form>
                @endif
                @unless($navigationMode)
                    <a href="{{ $directionsUrl }}" target="_blank" class="rounded-xl bg-red-600 px-4 py-4 text-center font-black text-white">Google Maps</a>
                    <button id="wakeLockButton" type="button" class="rounded-xl border border-slate-300 bg-white px-4 py-4 text-center font-black text-slate-800">Layar aktif</button>
                @else
                    <button id="wakeLockButton" type="button" class="hidden" aria-hidden="true" tabindex="-1">Layar aktif</button>
                @endunless
            </div>
        </div>
        @endunless

        @if($navigationMode)
            <details class="mx-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:mx-6 lg:mx-8">
                <summary class="cursor-pointer text-sm font-black text-slate-900">Detail laporan dan perangkat</summary>
                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    <a href="{{ $reporterPhone }}" class="rounded-xl bg-slate-50 p-3 hover:bg-red-50">
                        <p class="text-xs font-black uppercase text-slate-500">Pelapor</p>
                        <p class="mt-1 font-black">{{ $assignment->report->reporter_name }}</p>
                        <p class="text-sm text-slate-500">{{ $assignment->report->reporter_phone }}</p>
                    </a>
                    <a href="{{ $mapsUrl }}" target="_blank" class="rounded-xl bg-slate-50 p-3 hover:bg-red-50">
                        <p class="text-xs font-black uppercase text-slate-500">Lokasi kejadian</p>
                        <p class="mt-1 font-black">Buka titik lokasi</p>
                        <p class="text-sm text-slate-500">Koordinat laporan masyarakat</p>
                    </a>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-xs font-black uppercase text-slate-500">Perangkat</p>
                        <p id="deviceStatus" class="mt-1 text-sm font-semibold text-slate-600">GPS tetap dikirim selama halaman ini terbuka.</p>
                    </div>
                </div>
                <p class="mt-3 text-sm font-semibold text-slate-600">{{ $assignment->report->description }}</p>
            </details>
        @else
            <div class="grid gap-4 pb-24 md:grid-cols-3">
                <a href="{{ $reporterPhone }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:border-red-300">
                    <p class="text-xs font-black uppercase text-slate-500">Pelapor</p>
                    <p class="mt-1 font-black">{{ $assignment->report->reporter_name }}</p>
                    <p class="text-sm text-slate-500">{{ $assignment->report->reporter_phone }}</p>
                </a>
                <a href="{{ $mapsUrl }}" target="_blank" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:border-red-300">
                    <p class="text-xs font-black uppercase text-slate-500">Lokasi kejadian</p>
                    <p class="mt-1 font-black">Buka titik lokasi</p>
                    <p class="text-sm text-slate-500">Koordinat laporan masyarakat</p>
                </a>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-black uppercase text-slate-500">Perangkat</p>
                    <p id="deviceStatus" class="mt-1 text-sm font-semibold text-slate-600">GPS tetap dikirim selama halaman ini terbuka.</p>
                </div>
            </div>
        @endif
    </section>

    @push('scripts')
        @if($navigationMode)
            <script src="https://unpkg.com/leaflet-rotate@0.2.7/dist/leaflet-rotate-src.js"></script>
        @endif
        <script>
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const navigationMode = @json($navigationMode);
            const currentAssignmentId = @json($assignment->id);
            const reportPoint = [{{ $assignment->report->latitude }}, {{ $assignment->report->longitude }}];
            const map = L.map('assignmentMap', {
                zoomControl: false,
                zoomSnap: navigationMode ? 0.25 : 1,
                rotate: navigationMode,
                rotateControl: false,
                touchRotate: false,
                shiftKeyRotate: false,
            }).setView(reportPoint, navigationMode ? 17 : 14);
            L.control.zoom({ position: 'bottomright' }).addTo(map);
            TimsarMap.addTiles(map);

            const reportMarker = L.marker(reportPoint, { icon: TimsarMap.icon('incident') }).addTo(map).bindPopup('<strong>Lokasi kejadian</strong>');
            let routeLine = null;
            let routeSignature = '';
            let trailLines = [];
            let trailSignature = '';
            let currentRouteLatLngs = [];
            let memberMarker = null;
            let memberAccuracyCircle = null;
            let latestPosition = null;
            let bestWarmupPosition = null;
            let gpsWarmupStartedAt = null;
            let gpsWarmupSamples = 0;
            let gpsReady = false;
            let watchId = null;
            let wakeLock = null;
            let wakeLockWanted = false;
            let autoFollow = true;
            let autoFollowResumeTimeout = null;
            let autoFollowCountdownInterval = null;
            let autoFollowResumeAt = null;
            let navigationHeading = null;
            let headingAnchorPosition = null;
            let compassHeading = null;
            let compassUpdatedAt = 0;
            let lastCompassMapUpdateAt = 0;
            let locationSendInFlight = false;
            let lastLocationAttemptAt = 0;

            const initialRoute = @json($assignment->route_geometry_json);
            const targetAccuracyMeters = 50;
            const maxAcceptedAccuracyMeters = 120;
            const warmupMinSamples = 3;
            const warmupMaxMilliseconds = 12000;
            const autoFollowResumeMilliseconds = 10000;
            const gpsStatus = document.getElementById('gpsStatus');
            const accuracyValue = document.getElementById('accuracyValue');
            const lastSentValue = document.getElementById('lastSentValue');
            const networkStatus = document.getElementById('networkStatus');
            const trailDistanceValue = document.getElementById('trailDistanceValue');
            const cellStatusValue = document.getElementById('cellStatusValue');
            const deviceStatus = document.getElementById('deviceStatus');
            const wakeLockButton = document.getElementById('wakeLockButton');
            const focusMeButton = document.getElementById('focusMeButton');
            const fitRouteButton = document.getElementById('fitRouteButton');
            const distanceText = document.getElementById('distanceText');
            const durationText = document.getElementById('durationText');
            const assignmentStatusText = document.getElementById('assignmentStatusText');
            const mapRouteMeta = document.getElementById('mapRouteMeta');
            const routeDeviationNotice = document.getElementById('routeDeviationNotice');

            function networkType() {
                if (!navigator.onLine) return 'offline';
                const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
                return conn?.effectiveType || conn?.type || 'unknown';
            }

            function updateNetworkUi() {
                networkStatus.textContent = networkType();
            }

            window.addEventListener('timsar:cell-info', (event) => {
                const cell = window.TimsarNativeBridge?.cell();
                if (!cell || !cell.cell_id) return;
                cellStatusValue.textContent = `${cell.radio_type || 'CELL'} ${cell.cell_id}`;
                cellStatusValue.title = `${cell.operator_label || cell.operator_name || 'Operator'} - Cell ${cell.cell_id}`;
            });

            function formatDistance(meters) {
                if (!meters) return '-';
                return meters >= 1000 ? `${(meters / 1000).toFixed(2)} km` : `${Math.round(meters)} m`;
            }

            function formatDuration(seconds) {
                if (!seconds) return '-';
                return `${Math.max(1, Math.round(seconds / 60))} menit`;
            }

            function updateGpsUi(message, pos = latestPosition) {
                gpsStatus.textContent = message;
                accuracyValue.textContent = pos ? `${Math.round(pos.coords.accuracy)} m` : '-';
                updateNetworkUi();
            }

            function geometryToLatLngs(geometry) {
                if (!geometry || !geometry.coordinates) return [];
                return geometry.coordinates.map((point) => [point[1], point[0]]);
            }

            function clearTrailLines() {
                trailLines.forEach((line) => line.remove());
                trailLines = [];
            }

            function setTrailData(trail) {
                const signature = JSON.stringify(trail?.segments ?? []);
                if (signature === trailSignature) return;

                trailSignature = signature;
                clearTrailLines();

                (trail?.segments ?? []).forEach((segment) => {
                    const latLngs = (segment.points ?? []).map((point) => [point.latitude, point.longitude]);
                    if (latLngs.length < 2) return;

                    trailLines.push(L.polyline(latLngs, TimsarMap.trailOptions()).addTo(map));
                });

                const pointCount = trail?.summary?.point_count ?? 0;
                trailDistanceValue.textContent = pointCount > 0
                    ? (trail.summary.distance_meters > 0 ? formatDistance(trail.summary.distance_meters) : '0 m')
                    : '-';
            }

            function setRouteGeometry(geometry, shouldFit = false) {
                const latLngs = geometryToLatLngs(geometry);
                const signature = JSON.stringify(geometry?.coordinates ?? []);
                if (!latLngs.length || signature === routeSignature) return;

                routeSignature = signature;
                currentRouteLatLngs = latLngs;
                if (!routeLine) {
                    routeLine = L.polyline(latLngs, TimsarMap.routeOptions({ weight: 6 })).addTo(map);
                } else {
                    routeLine.setLatLngs(latLngs);
                }

                if (shouldFit) {
                    fitRoute();
                }

                updateRouteDeviationUi(latestPosition);
            }

            function fitRoute() {
                setAutoFollow(false, true);
                if (routeLine) {
                    map.fitBounds(routeLine.getBounds(), { padding: [36, 36] });
                    return;
                }
                map.setView(reportPoint, 15);
            }

            function focusMe() {
                if (!latestPosition) return;
                setAutoFollow(true);
                followNavigation(latestPosition, true);
            }

            function clearAutoFollowResume() {
                window.clearTimeout(autoFollowResumeTimeout);
                window.clearInterval(autoFollowCountdownInterval);
                autoFollowResumeTimeout = null;
                autoFollowCountdownInterval = null;
                autoFollowResumeAt = null;
            }

            function updateAutoFollowButton() {
                if (!navigationMode) return;

                const seconds = autoFollowResumeAt
                    ? Math.max(1, Math.ceil((autoFollowResumeAt - Date.now()) / 1000))
                    : null;
                focusMeButton.textContent = autoFollow
                    ? 'Mengikuti'
                    : (seconds ? `Ikuti ${seconds} dtk` : 'Ikuti');
                focusMeButton.className = autoFollow
                    ? 'min-w-24 rounded-xl bg-emerald-600 px-3 py-2 text-sm font-black text-white shadow-lg'
                    : 'min-w-24 rounded-xl bg-white/95 px-3 py-2 text-sm font-black text-slate-900 shadow-lg';
            }

            function scheduleAutoFollowResume() {
                clearAutoFollowResume();
                autoFollowResumeAt = Date.now() + autoFollowResumeMilliseconds;
                updateAutoFollowButton();
                autoFollowCountdownInterval = window.setInterval(updateAutoFollowButton, 1000);
                autoFollowResumeTimeout = window.setTimeout(() => {
                    setAutoFollow(true);
                    if (latestPosition) followNavigation(latestPosition, true);
                }, autoFollowResumeMilliseconds);
            }

            function setAutoFollow(enabled, resumeAfterDelay = false) {
                clearAutoFollowResume();
                autoFollow = enabled;
                updateAutoFollowButton();
                if (!enabled && resumeAfterDelay) scheduleAutoFollowResume();
            }

            function pauseAutoFollow() {
                if (navigationMode) setAutoFollow(false, true);
            }

            function normalizeHeading(value) {
                return ((value % 360) + 360) % 360;
            }

            function bearingBetween(from, to) {
                const lat1 = from.coords.latitude * Math.PI / 180;
                const lat2 = to.coords.latitude * Math.PI / 180;
                const dLon = (to.coords.longitude - from.coords.longitude) * Math.PI / 180;
                const y = Math.sin(dLon) * Math.cos(lat2);
                const x = Math.cos(lat1) * Math.sin(lat2) -
                    Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLon);
                return normalizeHeading(Math.atan2(y, x) * 180 / Math.PI);
            }

            function smoothHeading(current, next, weight = 0.28) {
                if (current === null) return next;
                const delta = ((next - current + 540) % 360) - 180;
                return normalizeHeading(current + (delta * weight));
            }

            function applyCompassHeading(value) {
                const parsed = Number(value);
                if (!Number.isFinite(parsed)) return;

                compassHeading = smoothHeading(compassHeading, normalizeHeading(parsed), 0.2);
                compassUpdatedAt = Date.now();

                const speedMps = Number.isFinite(latestPosition?.coords.speed)
                    ? latestPosition.coords.speed
                    : 0;
                if (!navigationMode || !autoFollow || speedMps >= 1.5) return;
                if (Date.now() - lastCompassMapUpdateAt < 120) return;

                navigationHeading = smoothHeading(navigationHeading, compassHeading, 0.22);
                lastCompassMapUpdateAt = Date.now();
                if (typeof map.setBearing === 'function') {
                    map.setBearing(navigationHeading);
                }
            }

            window.addEventListener('timsar:compass-heading', (event) => {
                applyCompassHeading(event.detail?.heading);
            });

            window.addEventListener('deviceorientationabsolute', (event) => {
                if (!Number.isFinite(event.alpha)) return;
                const screenAngle = window.screen.orientation?.angle ?? window.orientation ?? 0;
                applyCompassHeading(360 - event.alpha + screenAngle);
            });

            function updateNavigationHeading(pos) {
                if (!navigationMode) return;

                const speedMps = Number.isFinite(pos.coords.speed) ? pos.coords.speed : 0;
                const gpsHeading = Number.isFinite(pos.coords.heading) && pos.coords.heading >= 0
                    ? normalizeHeading(pos.coords.heading)
                    : null;
                const recentCompassHeading = Date.now() - compassUpdatedAt < 2000
                    ? compassHeading
                    : null;
                let nextHeading = speedMps >= 1.5 ? gpsHeading : recentCompassHeading;

                if (nextHeading === null && headingAnchorPosition) {
                    const movedMeters = distanceMeters(headingAnchorPosition, pos);
                    const movementThreshold = Math.max(8, Math.min(pos.coords.accuracy * 0.45, 25));
                    if (movedMeters >= movementThreshold) {
                        nextHeading = bearingBetween(headingAnchorPosition, pos);
                    }
                }

                if (nextHeading !== null) {
                    navigationHeading = smoothHeading(navigationHeading, nextHeading);
                    headingAnchorPosition = pos;
                } else if (!headingAnchorPosition) {
                    headingAnchorPosition = pos;
                }
            }

            function navigationZoom(pos) {
                const speedKph = (Number.isFinite(pos.coords.speed) ? pos.coords.speed : 0) * 3.6;
                if (speedKph >= 55) return 16.25;
                if (speedKph >= 25) return 16.75;
                if (speedKph >= 5) return 17.25;
                return 17.75;
            }

            function followNavigation(pos, immediate = false) {
                const point = [pos.coords.latitude, pos.coords.longitude];
                const zoom = navigationMode ? navigationZoom(pos) : Math.max(map.getZoom(), 16);

                if (navigationMode && navigationHeading !== null && typeof map.setBearing === 'function') {
                    map.setBearing(navigationHeading);
                }

                map.setView(point, zoom, { animate: !immediate });
                if (navigationMode) {
                    map.panBy([0, -Math.round(map.getSize().y * 0.2)], { animate: !immediate });
                }

            }

            function nearestPointOnSegment(point, start, end) {
                const earthRadius = 6371000;
                const latitudeReference = point.coords.latitude * Math.PI / 180;
                const toLocalPoint = (latLng) => ({
                    x: (latLng[1] - point.coords.longitude) * Math.PI / 180 * earthRadius * Math.cos(latitudeReference),
                    y: (latLng[0] - point.coords.latitude) * Math.PI / 180 * earthRadius,
                });
                const a = toLocalPoint(start);
                const b = toLocalPoint(end);
                const dx = b.x - a.x;
                const dy = b.y - a.y;
                const lengthSquared = (dx * dx) + (dy * dy);
                const projection = lengthSquared > 0
                    ? Math.max(0, Math.min(1, -((a.x * dx) + (a.y * dy)) / lengthSquared))
                    : 0;
                const nearestX = a.x + (projection * dx);
                const nearestY = a.y + (projection * dy);
                return {
                    distance: Math.sqrt((nearestX * nearestX) + (nearestY * nearestY)),
                    point: [
                        start[0] + ((end[0] - start[0]) * projection),
                        start[1] + ((end[1] - start[1]) * projection),
                    ],
                };
            }

            function nearestRouteMatch(pos) {
                if (currentRouteLatLngs.length < 2) return null;

                let nearest = null;
                for (let index = 1; index < currentRouteLatLngs.length; index += 1) {
                    const match = nearestPointOnSegment(
                        pos,
                        currentRouteLatLngs[index - 1],
                        currentRouteLatLngs[index],
                    );
                    if (!nearest || match.distance < nearest.distance) {
                        nearest = { ...match, nextIndex: index };
                    }
                }
                return nearest;
            }

            function updateRouteDeviationUi(pos) {
                if (!navigationMode || !pos || currentRouteLatLngs.length < 2) return false;

                const thresholdMeters = Math.max(70, Math.min(150, pos.coords.accuracy * 1.5));
                const nearest = nearestRouteMatch(pos);
                const deviated = nearest && nearest.distance > thresholdMeters;
                routeDeviationNotice.classList.toggle('hidden', !deviated);

                if (!deviated && nearest && routeLine) {
                    routeLine.setLatLngs([
                        nearest.point,
                        ...currentRouteLatLngs.slice(nearest.nextIndex),
                    ]);
                }
                return deviated;
            }

            function updateMemberMarker(pos) {
                const point = [pos.coords.latitude, pos.coords.longitude];
                if (!memberMarker) {
                    memberMarker = L.marker(point, { icon: TimsarMap.icon('member') }).addTo(map).bindPopup('<strong>Posisi saya</strong><br><span class="text-xs text-slate-500">Bergerak menuju lokasi</span>');
                } else {
                    TimsarMap.moveMarker(memberMarker, point);
                }

                if (!memberAccuracyCircle) {
                    memberAccuracyCircle = L.circle(point, {
                        radius: pos.coords.accuracy,
                        color: '#16a34a',
                        fillColor: '#22c55e',
                        fillOpacity: 0.08,
                        weight: 1,
                    }).addTo(map);
                } else {
                    memberAccuracyCircle.setLatLng(point);
                    memberAccuracyCircle.setRadius(pos.coords.accuracy);
                }

                if (autoFollow) {
                    followNavigation(pos);
                }
            }

            function positionFromServer(data) {
                if (!data || data.latitude === null || data.longitude === null) return null;

                return {
                    coords: {
                        latitude: Number(data.latitude),
                        longitude: Number(data.longitude),
                        accuracy: Number(data.accuracy ?? latestPosition?.coords.accuracy ?? 0),
                        speed: latestPosition?.coords.speed ?? null,
                    },
                    timestamp: Date.now(),
                };
            }

            function acceptGpsPosition(pos, message) {
                latestPosition = pos;
                gpsReady = true;
                updateNavigationHeading(pos);
                updateMemberMarker(pos);
                if (updateRouteDeviationUi(pos)) {
                    sendLocation();
                }
                updateGpsUi(message, pos);
            }

            function handleGpsPosition(pos) {
                const now = Date.now();
                gpsWarmupStartedAt ??= now;
                gpsWarmupSamples += 1;

                if (!bestWarmupPosition || pos.coords.accuracy < bestWarmupPosition.coords.accuracy) {
                    bestWarmupPosition = pos;
                }

                if (!gpsReady) {
                    const elapsed = now - gpsWarmupStartedAt;
                    const enoughSamples = gpsWarmupSamples >= warmupMinSamples;
                    const goodEarlyLock = bestWarmupPosition.coords.accuracy <= targetAccuracyMeters && gpsWarmupSamples >= 2;
                    const timeoutReached = elapsed >= warmupMaxMilliseconds;
                    updateGpsUi(`Mengunci GPS (${Math.min(gpsWarmupSamples, warmupMinSamples)}/${warmupMinSamples})`, bestWarmupPosition);

                    if (!enoughSamples && !goodEarlyLock && !timeoutReached) {
                        return;
                    }

                    acceptGpsPosition(bestWarmupPosition, `GPS aktif ${Math.round(bestWarmupPosition.coords.accuracy)} m`);
                    return;
                }

                if (
                    latestPosition &&
                    pos.coords.accuracy > maxAcceptedAccuracyMeters &&
                    pos.coords.accuracy > latestPosition.coords.accuracy
                ) {
                    updateGpsUi(`GPS melemah ${Math.round(pos.coords.accuracy)} m`, latestPosition);
                    return;
                }

                if (
                    latestPosition &&
                    pos.coords.accuracy > latestPosition.coords.accuracy * 1.8 &&
                    distanceMeters(latestPosition, pos) < pos.coords.accuracy
                ) {
                    updateGpsUi(`Titik kasar diabaikan ${Math.round(pos.coords.accuracy)} m`, latestPosition);
                    return;
                }

                acceptGpsPosition(pos, `GPS aktif ${Math.round(pos.coords.accuracy)} m`);
            }

            function startLocationWatch() {
                updateNetworkUi();
                if (!navigator.geolocation) {
                    updateGpsUi('Browser tidak mendukung GPS.');
                    return;
                }

                if (watchId !== null) return;

                watchId = navigator.geolocation.watchPosition(
                    handleGpsPosition,
                    (error) => updateGpsUi(geolocationErrorMessage(error)),
                    { enableHighAccuracy: true, timeout: 30000, maximumAge: 0 },
                );
            }

            async function sendLocation() {
                updateNetworkUi();

                if (!latestPosition || !gpsReady) {
                    updateGpsUi('Menunggu GPS terbaik...', bestWarmupPosition);
                    return;
                }
                if (window.TimsarNativeBackgroundActive) {
                    lastSentValue.textContent = 'Latar aktif';
                    return;
                }
                if (locationSendInFlight) return;
                if (Date.now() - lastLocationAttemptAt < 2500) return;

                const pos = latestPosition;
                locationSendInFlight = true;
                lastLocationAttemptAt = Date.now();
                try {
                    const payload = {
                        latitude: pos.coords.latitude,
                        longitude: pos.coords.longitude,
                        accuracy: pos.coords.accuracy,
                        speed: pos.coords.speed ? pos.coords.speed * 3.6 : null,
                        network_type: networkType(),
                        recorded_at: new Date().toISOString(),
                        cell: window.TimsarNativeBridge?.cell() ?? null,
                    };

                    const res = await fetch('{{ route('member.location.update') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: JSON.stringify(payload),
                    });

                    if (res.ok) {
                        const result = await res.json();
                        lastSentValue.textContent = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                        if (result.data && result.data.accepted_for_routing === false) {
                            const stablePosition = positionFromServer(result.data);
                            if (stablePosition) {
                                updateMemberMarker(stablePosition);
                            }
                            updateGpsUi(`Terkirim, posisi live ditahan ${Math.round(pos.coords.accuracy)} m`, stablePosition ?? pos);
                            return;
                        }
                        updateGpsUi(`Terkirim ${Math.round(pos.coords.accuracy)} m`, pos);
                        if (updateRouteDeviationUi(pos)) {
                            await refreshAssignment();
                        }
                    }
                } catch (error) {
                    updateGpsUi('Lokasi belum terkirim.', pos);
                } finally {
                    locationSendInFlight = false;
                }
            }

            async function sendHeartbeat() {
                try {
                    await fetch('{{ route('member.heartbeat') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: JSON.stringify({ network_type: networkType() }),
                    });
                } catch (error) {
                    //
                }
            }

            async function refreshAssignment() {
                try {
                    const res = await fetch('{{ route('member.active-assignment') }}', { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;

                    const data = await res.json();
                    if (!data.assignment) {
                        window.location.href = '{{ route('member.dashboard') }}';
                        return;
                    }
                    if (data.assignment.id !== currentAssignmentId) {
                        window.location.href = `{{ url('/member/assignments') }}/${data.assignment.id}`;
                        return;
                    }

                    assignmentStatusText.textContent = data.assignment.status_label;
                    distanceText.textContent = formatDistance(data.assignment.distance_meters);
                    durationText.textContent = formatDuration(data.assignment.duration_seconds);
                    mapRouteMeta.textContent = `${formatDistance(data.assignment.distance_meters)} - ${formatDuration(data.assignment.duration_seconds)}`;
                    setRouteGeometry(data.assignment.route_geometry);
                } catch (error) {
                    //
                }
            }

            async function refreshTrail() {
                try {
                    const res = await fetch('{{ route('member.assignments.trail', $assignment) }}', { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;

                    setTrailData(await res.json());
                } catch (error) {
                    //
                }
            }

            function distanceMeters(a, b) {
                const earthRadius = 6371000;
                const lat1 = a.coords.latitude * Math.PI / 180;
                const lat2 = b.coords.latitude * Math.PI / 180;
                const dLat = (b.coords.latitude - a.coords.latitude) * Math.PI / 180;
                const dLon = (b.coords.longitude - a.coords.longitude) * Math.PI / 180;
                const h = Math.sin(dLat / 2) ** 2 +
                    Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLon / 2) ** 2;
                return earthRadius * 2 * Math.atan2(Math.sqrt(h), Math.sqrt(1 - h));
            }

            function geolocationErrorMessage(error) {
                if (error.code === error.PERMISSION_DENIED) {
                    return 'Izin lokasi ditolak.';
                }
                if (error.code === error.POSITION_UNAVAILABLE) {
                    return 'GPS HP belum tersedia.';
                }
                if (error.code === error.TIMEOUT) {
                    return 'GPS terlalu lama merespons.';
                }

                return 'Gagal mengambil GPS.';
            }

            function updateWakeLockUi() {
                if (!('wakeLock' in navigator)) {
                    if (wakeLockButton && !navigationMode) {
                        wakeLockButton.disabled = true;
                        wakeLockButton.textContent = 'Tidak didukung';
                    }
                    deviceStatus.textContent = 'Browser ini belum mendukung layar tetap aktif.';
                    return;
                }

                if (wakeLockButton && !navigationMode) {
                    wakeLockButton.disabled = false;
                }

                if (wakeLock) {
                    if (wakeLockButton && !navigationMode) {
                        wakeLockButton.textContent = 'Layar aktif';
                        wakeLockButton.className = 'rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-center font-black text-emerald-700';
                    }
                    deviceStatus.textContent = 'Layar dijaga tetap aktif selama halaman tugas terbuka.';
                } else {
                    if (wakeLockButton && !navigationMode) {
                        wakeLockButton.textContent = 'Layar aktif';
                        wakeLockButton.className = 'rounded-xl border border-slate-300 bg-white px-4 py-4 text-center font-black text-slate-800';
                    }
                    deviceStatus.textContent = 'GPS tetap dikirim selama halaman ini terbuka.';
                }
            }

            async function requestWakeLock() {
                if (!('wakeLock' in navigator)) {
                    updateWakeLockUi();
                    return;
                }

                try {
                    wakeLock = await navigator.wakeLock.request('screen');
                    wakeLockWanted = true;
                    wakeLock.addEventListener('release', () => {
                        wakeLock = null;
                        updateWakeLockUi();
                    });
                } catch (error) {
                    deviceStatus.textContent = 'Gagal menjaga layar aktif. Matikan hemat baterai jika perlu.';
                }

                updateWakeLockUi();
            }

            async function releaseWakeLock() {
                wakeLockWanted = false;
                if (wakeLock) {
                    await wakeLock.release();
                    wakeLock = null;
                }
                updateWakeLockUi();
            }

            wakeLockButton?.addEventListener('click', () => {
                if (navigationMode) return;
                if (wakeLock) {
                    releaseWakeLock();
                    return;
                }
                requestWakeLock();
            });

            focusMeButton.addEventListener('click', focusMe);
            fitRouteButton.addEventListener('click', fitRoute);
            map.getContainer().addEventListener('pointerdown', pauseAutoFollow, { passive: true });
            map.getContainer().addEventListener('touchstart', pauseAutoFollow, { passive: true });
            map.getContainer().addEventListener('wheel', pauseAutoFollow, { passive: true });

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible' && wakeLockWanted && !wakeLock) {
                    requestWakeLock();
                }
            });

            setRouteGeometry(initialRoute, !navigationMode);
            mapRouteMeta.textContent = `${distanceText.textContent} - ${durationText.textContent}`;
            setAutoFollow(true);
            startLocationWatch();
            sendHeartbeat();
            sendLocation();
            refreshAssignment();
            refreshTrail();
            updateWakeLockUi();
            if (navigationMode) {
                requestWakeLock();
            }
            setInterval(sendHeartbeat, 10000);
            setInterval(sendLocation, 5000);
            setInterval(refreshAssignment, 5000);
            setInterval(refreshTrail, 5000);
        </script>
    @endpush
</x-layouts.app>
