<x-layouts.app title="Mode Tugas TIMSAR">
    @php
        $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . $assignment->report->latitude . ',' . $assignment->report->longitude;
        $directionsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . $assignment->report->latitude . ',' . $assignment->report->longitude;
        $reporterPhone = 'tel:' . preg_replace('/[^\d+]/', '', $assignment->report->reporter_phone);
    @endphp

    <section class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-xs font-black uppercase text-red-600">{{ $assignment->report->tracking_code }}</p>
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

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="relative">
                <div id="assignmentMap" class="h-[62vh] min-h-[430px] md:h-[680px]"></div>

                <div class="pointer-events-none absolute left-3 right-3 top-3 z-[500] flex items-start justify-between gap-3">
                    <div class="pointer-events-auto rounded-2xl bg-white/95 p-3 shadow-lg backdrop-blur">
                        <p class="text-[11px] font-black uppercase text-slate-500">Navigasi tugas</p>
                        <p id="mapRouteMeta" class="mt-1 text-sm font-black text-slate-900">Menunggu GPS terbaik...</p>
                        <div class="mt-2 flex flex-wrap gap-2 text-[11px] font-black text-slate-600">
                            <span class="inline-flex items-center gap-1"><span class="h-1.5 w-5 rounded-full bg-blue-600"></span>Jalur ditempuh</span>
                            <span class="inline-flex items-center gap-1"><span class="h-1.5 w-5 rounded-full bg-red-500"></span>Rute tersisa</span>
                        </div>
                    </div>
                    <div class="pointer-events-auto grid gap-2">
                        <button id="focusMeButton" type="button" class="rounded-xl bg-white/95 px-3 py-2 text-sm font-black text-slate-900 shadow-lg">Saya</button>
                        <button id="fitRouteButton" type="button" class="rounded-xl bg-white/95 px-3 py-2 text-sm font-black text-slate-900 shadow-lg">Rute</button>
                    </div>
                </div>

                <div class="pointer-events-none absolute bottom-3 left-3 right-3 z-[500]">
                    <div class="pointer-events-auto rounded-2xl bg-white/95 p-3 shadow-lg backdrop-blur">
                        <div class="grid grid-cols-4 gap-2 text-center">
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
                            <div>
                                <p class="text-[11px] font-black uppercase text-slate-500">Ditempuh</p>
                                <p id="trailDistanceValue" class="font-black">-</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sticky bottom-0 z-30 -mx-4 border-t border-slate-200 bg-white/95 px-4 py-3 shadow-[0_-12px_30px_rgba(15,23,42,0.08)] backdrop-blur">
            <div class="mx-auto grid max-w-7xl grid-cols-2 gap-2 lg:grid-cols-4">
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
                    <form method="POST" action="{{ route('member.assignments.arrive', $assignment) }}" class="col-span-2 lg:col-span-2">
                        @csrf
                        <button class="w-full rounded-xl bg-amber-500 px-4 py-4 font-black text-white">Sampai</button>
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
                <a href="{{ $directionsUrl }}" target="_blank" class="rounded-xl bg-red-600 px-4 py-4 text-center font-black text-white">Google Maps</a>
                <button id="wakeLockButton" type="button" class="rounded-xl border border-slate-300 bg-white px-4 py-4 text-center font-black text-slate-800">Layar aktif</button>
            </div>
        </div>

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
    </section>

    @push('scripts')
        <script>
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const reportPoint = [{{ $assignment->report->latitude }}, {{ $assignment->report->longitude }}];
            const map = L.map('assignmentMap', { zoomControl: false }).setView(reportPoint, 14);
            L.control.zoom({ position: 'bottomright' }).addTo(map);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

            const reportMarker = L.marker(reportPoint).addTo(map).bindPopup('Lokasi kejadian');
            let routeLine = null;
            let routeSignature = '';
            let trailLines = [];
            let trailSignature = '';
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
            let suppressMapInteraction = false;

            const initialRoute = @json($assignment->route_geometry_json);
            const targetAccuracyMeters = 50;
            const maxAcceptedAccuracyMeters = 120;
            const warmupMinSamples = 3;
            const warmupMaxMilliseconds = 12000;
            const gpsStatus = document.getElementById('gpsStatus');
            const accuracyValue = document.getElementById('accuracyValue');
            const lastSentValue = document.getElementById('lastSentValue');
            const networkStatus = document.getElementById('networkStatus');
            const trailDistanceValue = document.getElementById('trailDistanceValue');
            const deviceStatus = document.getElementById('deviceStatus');
            const wakeLockButton = document.getElementById('wakeLockButton');
            const focusMeButton = document.getElementById('focusMeButton');
            const fitRouteButton = document.getElementById('fitRouteButton');
            const distanceText = document.getElementById('distanceText');
            const durationText = document.getElementById('durationText');
            const assignmentStatusText = document.getElementById('assignmentStatusText');
            const mapRouteMeta = document.getElementById('mapRouteMeta');

            function networkType() {
                if (!navigator.onLine) return 'offline';
                const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
                return conn?.effectiveType || conn?.type || 'unknown';
            }

            function updateNetworkUi() {
                networkStatus.textContent = networkType();
            }

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

                    trailLines.push(L.polyline(latLngs, {
                        color: '#2563eb',
                        weight: 5,
                        opacity: 0.85,
                    }).addTo(map));
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
                if (!routeLine) {
                    routeLine = L.polyline(latLngs, { color: '#ef4444', weight: 6, opacity: 0.9 }).addTo(map);
                } else {
                    routeLine.setLatLngs(latLngs);
                }

                if (shouldFit) {
                    fitRoute();
                }
            }

            function fitRoute() {
                autoFollow = false;
                if (routeLine) {
                    map.fitBounds(routeLine.getBounds(), { padding: [36, 36] });
                    return;
                }
                map.setView(reportPoint, 15);
            }

            function focusMe() {
                if (!latestPosition) return;
                autoFollow = true;
                suppressMapInteraction = true;
                map.setView([latestPosition.coords.latitude, latestPosition.coords.longitude], Math.max(map.getZoom(), 16), { animate: true });
                window.setTimeout(() => {
                    suppressMapInteraction = false;
                }, 500);
            }

            function updateMemberMarker(pos) {
                const point = [pos.coords.latitude, pos.coords.longitude];
                if (!memberMarker) {
                    memberMarker = L.circleMarker(point, { radius: 10, color: '#16a34a', fillColor: '#22c55e', fillOpacity: .95 }).addTo(map).bindPopup('Posisi saya');
                } else {
                    memberMarker.setLatLng(point);
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
                    suppressMapInteraction = true;
                    map.setView(point, Math.max(map.getZoom(), 16), { animate: true });
                    window.setTimeout(() => {
                        suppressMapInteraction = false;
                    }, 500);
                }
            }

            function acceptGpsPosition(pos, message) {
                latestPosition = pos;
                gpsReady = true;
                updateMemberMarker(pos);
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

                const pos = latestPosition;
                try {
                    const payload = {
                        latitude: pos.coords.latitude,
                        longitude: pos.coords.longitude,
                        accuracy: pos.coords.accuracy,
                        speed: pos.coords.speed ? pos.coords.speed * 3.6 : null,
                        network_type: networkType(),
                        recorded_at: new Date().toISOString(),
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
                            updateGpsUi(`Terkirim, titik kasar tidak dipakai rute ${Math.round(pos.coords.accuracy)} m`, pos);
                            return;
                        }
                        updateGpsUi(`Terkirim ${Math.round(pos.coords.accuracy)} m`, pos);
                    }
                } catch (error) {
                    updateGpsUi('Lokasi belum terkirim.', pos);
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
                    if (!data.assignment) return;

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
                    wakeLockButton.disabled = true;
                    wakeLockButton.textContent = 'Tidak didukung';
                    deviceStatus.textContent = 'Browser ini belum mendukung layar tetap aktif.';
                    return;
                }

                wakeLockButton.disabled = false;
                if (wakeLock) {
                    wakeLockButton.textContent = 'Layar aktif';
                    wakeLockButton.className = 'rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-center font-black text-emerald-700';
                    deviceStatus.textContent = 'Layar dijaga tetap aktif selama halaman tugas terbuka.';
                } else {
                    wakeLockButton.textContent = 'Layar aktif';
                    wakeLockButton.className = 'rounded-xl border border-slate-300 bg-white px-4 py-4 text-center font-black text-slate-800';
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

            wakeLockButton.addEventListener('click', () => {
                if (wakeLock) {
                    releaseWakeLock();
                    return;
                }
                requestWakeLock();
            });

            focusMeButton.addEventListener('click', focusMe);
            fitRouteButton.addEventListener('click', fitRoute);
            map.on('dragstart zoomstart', () => {
                if (suppressMapInteraction) return;
                autoFollow = false;
            });

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible' && wakeLockWanted && !wakeLock) {
                    requestWakeLock();
                }
            });

            setRouteGeometry(initialRoute, true);
            mapRouteMeta.textContent = `${distanceText.textContent} - ${durationText.textContent}`;
            startLocationWatch();
            sendHeartbeat();
            sendLocation();
            refreshAssignment();
            refreshTrail();
            updateWakeLockUi();
            setInterval(sendHeartbeat, 10000);
            setInterval(sendLocation, 5000);
            setInterval(refreshAssignment, 5000);
            setInterval(refreshTrail, 5000);
        </script>
    @endpush
</x-layouts.app>
