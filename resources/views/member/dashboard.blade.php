<x-layouts.app title="Dashboard Anggota TIMSAR">
    <section class="grid gap-5 lg:grid-cols-[400px_1fr]">
        <aside class="space-y-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-black uppercase text-red-600">Mode lapangan</p>
                <h1 class="mt-1 text-2xl font-black">{{ auth()->user()->name }}</h1>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase text-slate-500">GPS petugas</p>
                            <p id="gpsStatus" class="mt-1 font-black">Mengaktifkan GPS...</p>
                        </div>
                        <span id="gpsQualityBadge" class="rounded-full bg-slate-200 px-3 py-1 text-xs font-black text-slate-700">Menunggu</span>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-2">
                        <div class="rounded-xl bg-white p-3">
                            <p class="text-[11px] font-black uppercase text-slate-500">Akurasi</p>
                            <p id="accuracyValue" class="mt-1 font-black">-</p>
                        </div>
                        <div class="rounded-xl bg-white p-3">
                            <p class="text-[11px] font-black uppercase text-slate-500">Terkirim</p>
                            <p id="lastSentValue" class="mt-1 font-black">-</p>
                        </div>
                        <div class="rounded-xl bg-white p-3">
                            <p class="text-[11px] font-black uppercase text-slate-500">Jaringan</p>
                            <p id="networkStatus" class="mt-1 font-black">-</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-2">
                        <button id="wakeLockButton" type="button" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-black text-slate-800">
                            Jaga layar tetap aktif
                        </button>
                        <button id="notificationButton" type="button" class="w-full rounded-xl border border-red-200 bg-white px-4 py-3 text-sm font-black text-red-700">
                            Aktifkan notifikasi tugas
                        </button>
                    </div>
                    <p id="deviceStatus" class="mt-3 text-xs font-semibold text-slate-500">GPS dikirim tiap 5 detik. Saat bertugas, aktifkan layar tetap aktif.</p>
                </div>
            </div>

            <div id="assignmentPanel" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase text-slate-500">Tugas aktif</p>
                        @if($activeAssignment)
                            <h2 class="mt-1 text-xl font-black">{{ $activeAssignment->report->incident_type }}</h2>
                            <p class="mt-1 text-sm font-semibold text-slate-500">{{ \App\Http\Controllers\PublicTrackingController::assignmentLabel($activeAssignment->status) }}</p>
                        @else
                            <h2 class="mt-1 text-xl font-black">Siaga</h2>
                            <p class="mt-1 text-sm font-semibold text-slate-500">Belum ada tugas dari posko.</p>
                        @endif
                    </div>
                </div>
                @if($activeAssignment)
                    <a href="{{ route('member.assignments.show', $activeAssignment) }}" class="mt-4 block rounded-xl bg-red-600 px-4 py-3 text-center font-black text-white">Buka mode tugas</a>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-black">Kasus posko</h2>
                <div class="mt-4 space-y-3">
                    @forelse($reports as $report)
                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="font-black">{{ $report->incident_type }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $report->tracking_code }} - {{ \App\Http\Controllers\PublicTrackingController::statusLabel($report->status) }}</p>
                        </div>
                    @empty
                        <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Belum ada kasus aktif.</p>
                    @endforelse
                </div>
            </div>
        </aside>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-xl font-black">Peta tugas</h2>
                <p id="routeMeta" class="text-sm text-slate-500">Menunggu data tugas dan GPS petugas.</p>
            </div>
            <div id="memberMap" class="h-[560px] md:h-[720px]"></div>
        </div>
    </section>

    @push('scripts')
        <script>
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const map = L.map('memberMap').setView([-8.586, 116.1], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

            let memberMarker = null;
            let memberAccuracyCircle = null;
            let reportMarker = null;
            let routeLine = null;
            let latestPosition = null;
            let bestWarmupPosition = null;
            let gpsWarmupStartedAt = null;
            let gpsWarmupSamples = 0;
            let gpsReady = false;
            let watchId = null;
            let lastAssignmentId = null;
            let assignmentLoaded = false;
            let assignmentAudioContext = null;
            let wakeLock = null;
            let wakeLockWanted = false;

            const targetAccuracyMeters = 50;
            const maxAcceptedAccuracyMeters = 120;
            const warmupMinSamples = 3;
            const warmupMaxMilliseconds = 12000;
            const gpsStatus = document.getElementById('gpsStatus');
            const gpsQualityBadge = document.getElementById('gpsQualityBadge');
            const accuracyValue = document.getElementById('accuracyValue');
            const lastSentValue = document.getElementById('lastSentValue');
            const networkStatus = document.getElementById('networkStatus');
            const deviceStatus = document.getElementById('deviceStatus');
            const notificationButton = document.getElementById('notificationButton');
            const wakeLockButton = document.getElementById('wakeLockButton');

            function networkType() {
                if (!navigator.onLine) return 'offline';
                const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
                return conn?.effectiveType || conn?.type || 'unknown';
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function updateNetworkUi() {
                networkStatus.textContent = networkType();
            }

            function gpsQuality(pos) {
                if (!pos) return ['Menunggu', 'bg-slate-200 text-slate-700'];
                if (pos.coords.accuracy <= targetAccuracyMeters) return ['Akurat', 'bg-emerald-100 text-emerald-700'];
                if (pos.coords.accuracy <= maxAcceptedAccuracyMeters) return ['Cukup', 'bg-amber-100 text-amber-700'];
                return ['Rendah', 'bg-red-100 text-red-700'];
            }

            function updateGpsUi(message, pos = latestPosition) {
                gpsStatus.textContent = message;
                const [label, className] = gpsQuality(pos);
                gpsQualityBadge.textContent = label;
                gpsQualityBadge.className = `rounded-full px-3 py-1 text-xs font-black ${className}`;
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
                    updateGpsUi(`Mengunci GPS awal (${Math.min(gpsWarmupSamples, warmupMinSamples)}/${warmupMinSamples})`, bestWarmupPosition);

                    if (!enoughSamples && !goodEarlyLock && !timeoutReached) {
                        return;
                    }

                    acceptGpsPosition(bestWarmupPosition, `GPS aktif - akurasi ${Math.round(bestWarmupPosition.coords.accuracy)} m`);
                    return;
                }

                if (
                    latestPosition &&
                    pos.coords.accuracy > maxAcceptedAccuracyMeters &&
                    pos.coords.accuracy > latestPosition.coords.accuracy
                ) {
                    updateGpsUi(`GPS melemah (${Math.round(pos.coords.accuracy)} m), memakai titik terbaik sebelumnya.`, latestPosition);
                    return;
                }

                if (
                    latestPosition &&
                    pos.coords.accuracy > latestPosition.coords.accuracy * 1.8 &&
                    distanceMeters(latestPosition, pos) < pos.coords.accuracy
                ) {
                    updateGpsUi(`Titik kasar diabaikan (${Math.round(pos.coords.accuracy)} m).`, latestPosition);
                    return;
                }

                acceptGpsPosition(pos, `GPS aktif - akurasi ${Math.round(pos.coords.accuracy)} m`);
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
                    updateGpsUi('Menunggu GPS mengunci titik terbaik...', bestWarmupPosition);
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
                        updateGpsUi(`Terkirim - akurasi ${Math.round(pos.coords.accuracy)} m`, pos);
                    }
                } catch (error) {
                    updateGpsUi('Lokasi belum terkirim. Periksa koneksi internet.', pos);
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
                    return 'Izin lokasi ditolak. Izinkan lokasi presisi untuk situs TIMSAR.';
                }
                if (error.code === error.POSITION_UNAVAILABLE) {
                    return 'Lokasi belum tersedia. Pastikan GPS HP aktif dan mode presisi menyala.';
                }
                if (error.code === error.TIMEOUT) {
                    return 'GPS terlalu lama merespons. Coba di area lebih terbuka.';
                }

                return 'Gagal mengambil GPS. Pastikan GPS dan izin lokasi browser aktif.';
            }

            function geometryToLatLngs(geometry) {
                if (!geometry || !geometry.coordinates) return [];
                return geometry.coordinates.map((point) => [point[1], point[0]]);
            }

            function updateNotificationUi() {
                if (!('Notification' in window)) {
                    notificationButton.disabled = true;
                    notificationButton.textContent = 'Notifikasi tidak didukung';
                    return;
                }

                if (Notification.permission === 'granted') {
                    notificationButton.disabled = false;
                    notificationButton.textContent = 'Notifikasi tugas aktif';
                    notificationButton.className = 'w-full rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-700';
                    return;
                }

                if (Notification.permission === 'denied') {
                    notificationButton.disabled = true;
                    notificationButton.textContent = 'Notifikasi diblokir';
                    return;
                }

                notificationButton.disabled = false;
                notificationButton.textContent = 'Aktifkan notifikasi tugas';
            }

            notificationButton.addEventListener('click', async () => {
                unlockAssignmentAudio();
                if ('Notification' in window) {
                    await Notification.requestPermission();
                }
                updateNotificationUi();
            });

            function unlockAssignmentAudio() {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;

                assignmentAudioContext ??= new AudioContext();
                if (assignmentAudioContext.state === 'suspended') {
                    assignmentAudioContext.resume();
                }
            }

            function playAssignmentTone() {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;

                const context = assignmentAudioContext || new AudioContext();
                if (context.state === 'suspended') {
                    context.resume();
                }

                const oscillator = context.createOscillator();
                const gain = context.createGain();
                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(880, context.currentTime);
                oscillator.frequency.setValueAtTime(660, context.currentTime + 0.18);
                gain.gain.setValueAtTime(0.001, context.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.25, context.currentTime + 0.03);
                gain.gain.exponentialRampToValueAtTime(0.001, context.currentTime + 0.45);
                oscillator.connect(gain);
                gain.connect(context.destination);
                oscillator.start();
                oscillator.stop(context.currentTime + 0.5);
            }

            function notifyNewAssignment(assignment) {
                document.title = 'Tugas baru - TIMSAR';
                if ('vibrate' in navigator) {
                    navigator.vibrate([250, 120, 250]);
                }

                playAssignmentTone();

                if ('Notification' in window && Notification.permission === 'granted') {
                    const notification = new Notification('Tugas TIMSAR baru', {
                        body: `${assignment.report.incident_type}. Tekan untuk membuka tugas.`,
                        tag: `assignment-${assignment.id}`,
                        requireInteraction: true,
                    });

                    notification.onclick = () => {
                        window.focus();
                        window.location.href = `/member/assignments/${assignment.id}`;
                    };
                }
            }

            function updateWakeLockUi() {
                if (!('wakeLock' in navigator)) {
                    wakeLockButton.disabled = true;
                    wakeLockButton.textContent = 'Layar aktif tidak didukung';
                    deviceStatus.textContent = 'Browser ini belum mendukung fitur layar tetap aktif.';
                    return;
                }

                wakeLockButton.disabled = false;
                if (wakeLock) {
                    wakeLockButton.textContent = 'Layar tetap aktif';
                    wakeLockButton.className = 'w-full rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-700';
                    deviceStatus.textContent = 'Layar dijaga tetap aktif selama halaman ini terbuka.';
                } else {
                    wakeLockButton.textContent = 'Jaga layar tetap aktif';
                    wakeLockButton.className = 'w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-black text-slate-800';
                    deviceStatus.textContent = 'GPS dikirim tiap 5 detik. Saat bertugas, aktifkan layar tetap aktif.';
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
                    deviceStatus.textContent = 'Layar tetap aktif gagal. Pastikan browser memakai HTTPS dan baterai tidak hemat daya.';
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

            async function refreshAssignment() {
                const res = await fetch('{{ route('member.active-assignment') }}', { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;

                const data = await res.json();
                const assignment = data.assignment;

                if (!assignment) {
                    lastAssignmentId = null;
                    assignmentLoaded = true;
                    document.getElementById('assignmentPanel').innerHTML = `
                        <div>
                            <p class="text-xs font-black uppercase text-slate-500">Tugas aktif</p>
                            <h2 class="mt-1 text-xl font-black">Siaga</h2>
                            <p class="mt-1 text-sm font-semibold text-slate-500">Belum ada tugas dari posko.</p>
                        </div>
                    `;
                    if (routeLine) {
                        routeLine.remove();
                        routeLine = null;
                    }
                    if (reportMarker) {
                        reportMarker.remove();
                        reportMarker = null;
                    }
                    document.getElementById('routeMeta').textContent = 'Belum ada tugas aktif.';
                    return;
                }

                if (assignmentLoaded && lastAssignmentId !== assignment.id) {
                    notifyNewAssignment(assignment);
                }
                lastAssignmentId = assignment.id;
                assignmentLoaded = true;

                document.getElementById('assignmentPanel').innerHTML = `
                    <div>
                        <p class="text-xs font-black uppercase text-slate-500">Tugas aktif</p>
                        <h2 class="mt-1 text-xl font-black">${escapeHtml(assignment.report.incident_type)}</h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500">${escapeHtml(assignment.status_label)}</p>
                        <p class="mt-3 text-sm text-slate-600">${escapeHtml(assignment.report.tracking_code)}</p>
                    </div>
                    <a href="/member/assignments/${assignment.id}" class="mt-4 block rounded-xl bg-red-600 px-4 py-3 text-center font-black text-white">Buka mode tugas</a>
                `;

                const reportPoint = [assignment.report.latitude, assignment.report.longitude];
                if (!reportMarker) {
                    reportMarker = L.marker(reportPoint).addTo(map).bindPopup('Lokasi kejadian');
                } else {
                    reportMarker.setLatLng(reportPoint);
                }

                const latLngs = geometryToLatLngs(assignment.route_geometry);
                if (routeLine) routeLine.remove();
                if (latLngs.length) {
                    routeLine = L.polyline(latLngs, { color: '#ef4444', weight: 5 }).addTo(map);
                    map.fitBounds(routeLine.getBounds(), { padding: [30, 30] });
                }
                const distance = assignment.distance_meters ? (assignment.distance_meters / 1000).toFixed(2) + ' km' : '-';
                const duration = assignment.duration_seconds ? Math.round(assignment.duration_seconds / 60) + ' menit' : '-';
                document.getElementById('routeMeta').textContent = `Jarak ${distance}, estimasi ${duration}.`;
            }

            startLocationWatch();
            sendHeartbeat();
            sendLocation();
            refreshAssignment();
            updateNotificationUi();
            updateWakeLockUi();
            setInterval(sendHeartbeat, 10000);
            setInterval(sendLocation, 5000);
            setInterval(refreshAssignment, 3000);
        </script>
    @endpush
</x-layouts.app>
