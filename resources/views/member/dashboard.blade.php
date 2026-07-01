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

            function networkType() {
                if (!navigator.onLine) return 'offline';
                const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
                return conn?.effectiveType || conn?.type || 'unknown';
            }

            function sendLocation() {
                document.getElementById('networkStatus').textContent = `Jaringan: ${networkType()}`;
                if (!navigator.geolocation) {
                    document.getElementById('gpsStatus').textContent = 'Browser tidak mendukung GPS.';
                    return;
                }

                navigator.geolocation.getCurrentPosition(async (pos) => {
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
                        const point = [payload.latitude, payload.longitude];
                        if (!memberMarker) {
                            memberMarker = L.circleMarker(point, { radius: 9, color: '#16a34a', fillColor: '#22c55e', fillOpacity: .9 }).addTo(map).bindPopup('Posisi saya');
                        } else {
                            memberMarker.setLatLng(point);
                        }
                    }
                }, (error) => {
                    document.getElementById('gpsStatus').textContent = geolocationErrorMessage(error);
                }, { enableHighAccuracy: true, timeout: 30000, maximumAge: 0 });
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
                const res = await fetch('{{ route('member.active-assignment') }}');
                const data = await res.json();
                const assignment = data.assignment;

                if (!assignment) {
                    document.getElementById('routeMeta').textContent = 'Belum ada tugas aktif.';
                    return;
                }

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

            sendLocation();
            refreshAssignment();
            setInterval(sendLocation, 5000);
            setInterval(refreshAssignment, 5000);
        </script>
    @endpush
</x-layouts.app>
