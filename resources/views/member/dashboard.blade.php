<x-layouts.app title="Dashboard Anggota TIMSAR">
    <section class="grid gap-5 lg:grid-cols-[380px_1fr]">
        <aside class="space-y-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-black uppercase text-red-600">Anggota lapangan</p>
                <h1 class="mt-1 text-2xl font-black">{{ auth()->user()->name }}</h1>
                <div class="mt-4 rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase text-slate-500">Status GPS</p>
                    <p id="gpsStatus" class="mt-1 font-black">Menunggu izin lokasi...</p>
                    <p id="networkStatus" class="mt-1 text-sm text-slate-500">Jaringan: -</p>
                    <p class="mt-2 text-xs font-semibold text-slate-500">Lokasi dikirim ke server tiap 5 detik dari HP anggota.</p>
                    <button id="notificationButton" type="button" class="mt-4 w-full rounded-xl border border-red-200 bg-white px-4 py-3 text-sm font-black text-red-700">
                        Aktifkan notifikasi tugas
                    </button>
                    <p id="notificationStatus" class="mt-2 text-xs font-semibold text-slate-500">Notifikasi membantu saat halaman terbuka di background.</p>
                </div>
            </div>

            <div id="assignmentPanel" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-black">Tugas aktif</h2>
                @if($activeAssignment)
                    <p class="mt-2 font-black">{{ $activeAssignment->report->incident_type }}</p>
                    <p class="text-sm text-slate-500">{{ \App\Http\Controllers\PublicTrackingController::assignmentLabel($activeAssignment->status) }}</p>
                    <a href="{{ route('member.assignments.show', $activeAssignment) }}" class="mt-4 block rounded-xl bg-red-600 px-4 py-3 text-center font-black text-white">Buka tugas</a>
                @else
                    <p class="mt-2 text-sm text-slate-500">Belum ada tugas dari posko.</p>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-black">Laporan aktif</h2>
                <div class="mt-4 space-y-3">
                    @foreach($reports as $report)
                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="font-black">{{ $report->incident_type }}</p>
                            <p class="text-sm text-slate-500">{{ \App\Http\Controllers\PublicTrackingController::statusLabel($report->status) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-xl font-black">Peta tugas</h2>
                <p id="routeMeta" class="text-sm text-slate-500">Menunggu data tugas dan lokasi.</p>
            </div>
            <div id="memberMap" class="h-[720px]"></div>
        </div>
    </section>

    @push('scripts')
        <script>
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const map = L.map('memberMap').setView([-8.586, 116.1], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
            let memberMarker = null;
            let reportMarker = null;
            let routeLine = null;
            let latestPosition = null;
            let watchId = null;
            let lastAssignmentId = null;
            let assignmentLoaded = false;
            let assignmentAudioContext = null;
            const maxAcceptedAccuracyMeters = 80;
            const notificationButton = document.getElementById('notificationButton');
            const notificationStatus = document.getElementById('notificationStatus');

            function networkType() {
                if (!navigator.onLine) return 'offline';
                const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
                return conn?.effectiveType || conn?.type || 'unknown';
            }

            function updateNotificationUi() {
                if (!('Notification' in window)) {
                    notificationButton.disabled = true;
                    notificationButton.textContent = 'Notifikasi tidak didukung';
                    notificationStatus.textContent = 'Browser ini belum mendukung notifikasi tugas.';
                    return;
                }

                if (Notification.permission === 'granted') {
                    notificationButton.disabled = false;
                    notificationButton.textContent = 'Notifikasi tugas aktif';
                    notificationButton.className = 'mt-4 w-full rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-700';
                    notificationStatus.textContent = 'Tugas baru akan memunculkan notifikasi, bunyi, dan getar jika didukung.';
                    return;
                }

                if (Notification.permission === 'denied') {
                    notificationButton.disabled = true;
                    notificationButton.textContent = 'Notifikasi diblokir';
                    notificationStatus.textContent = 'Aktifkan ulang izin notifikasi dari pengaturan browser.';
                    return;
                }

                notificationButton.textContent = 'Aktifkan notifikasi tugas';
                notificationButton.disabled = false;
                notificationStatus.textContent = 'Tekan sekali agar tugas baru bisa muncul sebagai notifikasi.';
            }

            notificationButton.addEventListener('click', async () => {
                if (!('Notification' in window)) {
                    updateNotificationUi();
                    return;
                }

                unlockAssignmentAudio();
                await Notification.requestPermission();
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

            function startLocationWatch() {
                document.getElementById('networkStatus').textContent = `Jaringan: ${networkType()}`;
                if (!navigator.geolocation) {
                    document.getElementById('gpsStatus').textContent = 'Browser tidak mendukung GPS.';
                    return;
                }

                if (watchId !== null) return;

                watchId = navigator.geolocation.watchPosition((pos) => {
                    if (pos.coords.accuracy > maxAcceptedAccuracyMeters && latestPosition) {
                        document.getElementById('gpsStatus').textContent =
                            `GPS kurang akurat (${Math.round(pos.coords.accuracy)} m), menunggu titik lebih baik.`;
                        return;
                    }

                    if (
                        latestPosition &&
                        pos.coords.accuracy > latestPosition.coords.accuracy * 1.8 &&
                        distanceMeters(latestPosition, pos) < pos.coords.accuracy
                    ) {
                        document.getElementById('gpsStatus').textContent =
                            `Titik baru diabaikan karena lebih kasar (${Math.round(pos.coords.accuracy)} m).`;
                        return;
                    }

                    latestPosition = pos;
                    const accuracyNote = pos.coords.accuracy > maxAcceptedAccuracyMeters
                        ? 'akurasi rendah'
                        : 'GPS aktif';
                    document.getElementById('gpsStatus').textContent = `${accuracyNote} - akurasi ${Math.round(pos.coords.accuracy)} m`;
                    const point = [pos.coords.latitude, pos.coords.longitude];
                    if (!memberMarker) {
                        memberMarker = L.circleMarker(point, { radius: 9, color: '#16a34a', fillColor: '#22c55e', fillOpacity: .9 }).addTo(map).bindPopup('Posisi saya');
                    } else {
                        memberMarker.setLatLng(point);
                    }
                }, (error) => {
                    document.getElementById('gpsStatus').textContent = geolocationErrorMessage(error);
                }, { enableHighAccuracy: true, timeout: 30000, maximumAge: 0 });
            }

            async function sendLocation() {
                document.getElementById('networkStatus').textContent = `Jaringan: ${networkType()}`;

                if (!latestPosition) {
                    document.getElementById('gpsStatus').textContent = 'Menunggu GPS mengunci lokasi...';
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
                        document.getElementById('gpsStatus').textContent = `Terkirim ${new Date().toLocaleTimeString('id-ID')} - akurasi ${Math.round(pos.coords.accuracy)} m`;
                    }
                } catch (error) {
                    document.getElementById('gpsStatus').textContent = 'Lokasi belum terkirim. Periksa koneksi internet.';
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
                    return 'Izin lokasi ditolak. Izinkan lokasi untuk situs TIMSAR di pengaturan browser.';
                }
                if (error.code === error.POSITION_UNAVAILABLE) {
                    return 'Lokasi belum tersedia. Pastikan GPS HP aktif dan mode lokasi presisi menyala.';
                }
                if (error.code === error.TIMEOUT) {
                    return 'GPS terlalu lama merespons. Coba lagi di area yang lebih terbuka.';
                }

                return 'Gagal mengambil GPS. Pastikan GPS dan izin lokasi browser aktif.';
            }

            function geometryToLatLngs(geometry) {
                if (!geometry || !geometry.coordinates) return [];
                return geometry.coordinates.map((point) => [point[1], point[0]]);
            }

            async function refreshAssignment() {
                const res = await fetch('{{ route('member.active-assignment') }}', { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;

                const data = await res.json();
                const assignment = data.assignment;

                if (!assignment) {
                    lastAssignmentId = null;
                    assignmentLoaded = true;
                    document.getElementById('assignmentPanel').innerHTML = `
                        <h2 class="text-xl font-black">Tugas aktif</h2>
                        <p class="mt-2 text-sm text-slate-500">Belum ada tugas dari posko.</p>
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
                    <h2 class="text-xl font-black">Tugas aktif</h2>
                    <p class="mt-2 font-black">${assignment.report.incident_type}</p>
                    <p class="text-sm text-slate-500">${assignment.status_label}</p>
                    <a href="/member/assignments/${assignment.id}" class="mt-4 block rounded-xl bg-red-600 px-4 py-3 text-center font-black text-white">Buka tugas</a>
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
            setInterval(sendHeartbeat, 10000);
            setInterval(sendLocation, 5000);
            setInterval(refreshAssignment, 3000);
        </script>
    @endpush
</x-layouts.app>
