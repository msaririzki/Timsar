<x-layouts.app title="Detail Tugas TIMSAR">
    @php
        $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . $assignment->report->latitude . ',' . $assignment->report->longitude;
        $directionsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . $assignment->report->latitude . ',' . $assignment->report->longitude;
        $reporterPhone = 'tel:' . preg_replace('/[^\d+]/', '', $assignment->report->reporter_phone);
    @endphp

    <section class="mx-auto max-w-5xl space-y-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-start">
                <div>
                    <p class="text-sm font-black uppercase text-red-600">{{ $assignment->report->tracking_code }}</p>
                    <h1 class="mt-1 text-3xl font-black">{{ $assignment->report->incident_type }}</h1>
                    <p class="mt-2 text-slate-600">{{ $assignment->report->description }}</p>
                </div>
                <a href="{{ route('member.dashboard') }}" class="rounded-xl bg-slate-900 px-4 py-3 text-center text-sm font-black text-white">
                    Dashboard
                </a>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-4">
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase text-slate-500">Status</p>
                    <p class="font-black">{{ \App\Http\Controllers\PublicTrackingController::assignmentLabel($assignment->status) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase text-slate-500">Jarak</p>
                    <p class="font-black">{{ $assignment->distance_meters ? number_format($assignment->distance_meters / 1000, 2) . ' km' : '-' }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase text-slate-500">Estimasi</p>
                    <p class="font-black">{{ $assignment->duration_seconds ? round($assignment->duration_seconds / 60) . ' menit' : '-' }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase text-slate-500">GPS saya</p>
                    <p id="gpsStatus" class="font-black">Mengaktifkan...</p>
                </div>
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-4">
            <button id="wakeLockButton" type="button" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-center font-black text-slate-800">
                Jaga layar aktif
            </button>
            <a href="{{ $directionsUrl }}" target="_blank" class="rounded-xl bg-red-600 px-4 py-3 text-center font-black text-white">
                Buka rute
            </a>
            <a href="{{ $mapsUrl }}" target="_blank" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-center font-black text-slate-800">
                Lihat lokasi
            </a>
            <a href="{{ $reporterPhone }}" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-center font-black text-slate-800">
                Hubungi pelapor
            </a>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            @if($assignment->status === 'assigned')
                <form method="POST" action="{{ route('member.assignments.accept', $assignment) }}">
                    @csrf
                    <button class="w-full rounded-xl bg-slate-900 px-4 py-4 font-black text-white">Terima</button>
                </form>
            @endif
            @if(in_array($assignment->status, ['assigned', 'accepted']))
                <form method="POST" action="{{ route('member.assignments.start', $assignment) }}">
                    @csrf
                    <button class="w-full rounded-xl bg-blue-600 px-4 py-4 font-black text-white">Mulai jalan</button>
                </form>
            @endif
            @if($assignment->status === 'on_the_way')
                <form method="POST" action="{{ route('member.assignments.arrive', $assignment) }}">
                    @csrf
                    <button class="w-full rounded-xl bg-amber-500 px-4 py-4 font-black text-white">Sampai</button>
                </form>
            @endif
            @if(in_array($assignment->status, ['arrived', 'on_the_way']))
                <form method="POST" action="{{ route('member.assignments.handling', $assignment) }}">
                    @csrf
                    <button class="w-full rounded-xl bg-purple-600 px-4 py-4 font-black text-white">Tangani</button>
                </form>
            @endif
            @if(in_array($assignment->status, ['handling', 'arrived']))
                <form method="POST" action="{{ route('member.assignments.complete', $assignment) }}">
                    @csrf
                    <button class="w-full rounded-xl bg-emerald-600 px-4 py-4 font-black text-white">Selesai</button>
                </form>
            @endif
        </div>

        <div class="grid gap-5 lg:grid-cols-[1fr_320px]">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div id="assignmentMap" class="h-[560px]"></div>
            </div>

            <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-black">Kondisi perangkat</h2>
                <div class="mt-4 grid gap-3">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase text-slate-500">Akurasi GPS</p>
                        <p id="accuracyValue" class="font-black">-</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase text-slate-500">Terkirim</p>
                        <p id="lastSentValue" class="font-black">-</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase text-slate-500">Jaringan</p>
                        <p id="networkStatus" class="font-black">-</p>
                    </div>
                    <p id="deviceStatus" class="rounded-xl bg-slate-50 p-4 text-sm font-semibold text-slate-600">
                        GPS tetap dikirim selama halaman ini terbuka.
                    </p>
                </div>
            </aside>
        </div>
    </section>

    @push('scripts')
        <script>
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const reportPoint = [{{ $assignment->report->latitude }}, {{ $assignment->report->longitude }}];
            const map = L.map('assignmentMap').setView(reportPoint, 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
            L.marker(reportPoint).addTo(map).bindPopup('Lokasi kejadian');

            @if($assignment->route_geometry_json)
                const route = @json($assignment->route_geometry_json);
                const latLngs = route.coordinates.map((point) => [point[1], point[0]]);
                const line = L.polyline(latLngs, { color: '#ef4444', weight: 5 }).addTo(map);
                map.fitBounds(line.getBounds(), { padding: [30, 30] });
            @endif

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

            const targetAccuracyMeters = 50;
            const maxAcceptedAccuracyMeters = 120;
            const warmupMinSamples = 3;
            const warmupMaxMilliseconds = 12000;
            const gpsStatus = document.getElementById('gpsStatus');
            const accuracyValue = document.getElementById('accuracyValue');
            const lastSentValue = document.getElementById('lastSentValue');
            const networkStatus = document.getElementById('networkStatus');
            const deviceStatus = document.getElementById('deviceStatus');
            const wakeLockButton = document.getElementById('wakeLockButton');

            function networkType() {
                if (!navigator.onLine) return 'offline';
                const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
                return conn?.effectiveType || conn?.type || 'unknown';
            }

            function updateNetworkUi() {
                networkStatus.textContent = networkType();
            }

            function updateGpsUi(message, pos = latestPosition) {
                gpsStatus.textContent = message;
                accuracyValue.textContent = pos ? `${Math.round(pos.coords.accuracy)} m` : '-';
                updateNetworkUi();
            }

            function updateMemberMarker(pos) {
                const point = [pos.coords.latitude, pos.coords.longitude];
                if (!memberMarker) {
                    memberMarker = L.circleMarker(point, { radius: 9, color: '#16a34a', fillColor: '#22c55e', fillOpacity: .9 }).addTo(map).bindPopup('Posisi saya');
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
                        lastSentValue.textContent = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
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
                    wakeLockButton.textContent = 'Layar aktif tidak didukung';
                    deviceStatus.textContent = 'Browser ini belum mendukung layar tetap aktif.';
                    return;
                }

                wakeLockButton.disabled = false;
                if (wakeLock) {
                    wakeLockButton.textContent = 'Layar tetap aktif';
                    wakeLockButton.className = 'rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-center font-black text-emerald-700';
                    deviceStatus.textContent = 'Layar dijaga tetap aktif selama halaman tugas terbuka.';
                } else {
                    wakeLockButton.textContent = 'Jaga layar aktif';
                    wakeLockButton.className = 'rounded-xl border border-slate-300 bg-white px-4 py-3 text-center font-black text-slate-800';
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

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible' && wakeLockWanted && !wakeLock) {
                    requestWakeLock();
                }
            });

            startLocationWatch();
            sendHeartbeat();
            sendLocation();
            updateWakeLockUi();
            setInterval(sendHeartbeat, 10000);
            setInterval(sendLocation, 5000);
        </script>
    @endpush
</x-layouts.app>
