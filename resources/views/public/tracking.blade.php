<x-layouts.app title="Tracking {{ $report->tracking_code }}">
    <section class="grid gap-5 lg:grid-cols-[380px_1fr]">
        <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-black uppercase text-red-600">Kode tracking</p>
            <h1 class="mt-1 text-3xl font-black">{{ $report->tracking_code }}</h1>
            <div class="mt-5 space-y-3">
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase text-slate-500">Status laporan</p>
                    <p id="statusLabel" class="mt-1 text-lg font-black">Memuat...</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase text-slate-500">Petugas</p>
                    <p id="memberName" class="mt-1 font-black">Belum ditugaskan</p>
                    <p id="memberMeta" class="text-sm text-slate-500">Menunggu posko.</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase text-slate-500">Jarak</p>
                        <p id="distanceText" class="mt-1 font-black">-</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase text-slate-500">Estimasi</p>
                        <p id="durationText" class="mt-1 font-black">-</p>
                    </div>
                </div>
            </div>
        </aside>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div id="map" class="h-[70vh] min-h-[420px]"></div>
        </div>
    </section>

    @push('scripts')
        <script>
            const reportPoint = [{{ $report->latitude }}, {{ $report->longitude }}];
            const map = L.map('map').setView(reportPoint, 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
            const reportMarker = L.marker(reportPoint).addTo(map).bindPopup('Lokasi pelapor');
            let memberMarker = null;
            let routeLine = null;

            function formatDistance(meters) {
                if (!meters) return '-';
                return meters >= 1000 ? `${(meters / 1000).toFixed(2)} km` : `${Math.round(meters)} m`;
            }

            function formatDuration(seconds) {
                if (!seconds) return '-';
                return seconds >= 60 ? `${Math.round(seconds / 60)} menit` : `${seconds} detik`;
            }

            function geometryToLatLngs(geometry) {
                if (!geometry || !geometry.coordinates) return [];
                return geometry.coordinates.map((point) => [point[1], point[0]]);
            }

            async function refreshTracking() {
                const res = await fetch('{{ route('public.tracking.data', $report->tracking_code) }}');
                const data = await res.json();
                document.getElementById('statusLabel').textContent = data.report.status_label;
                document.getElementById('distanceText').textContent = formatDistance(data.assignment?.distance_meters);
                document.getElementById('durationText').textContent = formatDuration(data.assignment?.duration_seconds);

                if (data.member) {
                    document.getElementById('memberName').textContent = data.member.name;
                    document.getElementById('memberMeta').textContent = `${data.member.network_type || 'unknown'} - update ${data.member.last_seen_at ? new Date(data.member.last_seen_at).toLocaleTimeString('id-ID') : '-'}`;
                    if (data.member.latitude && data.member.longitude) {
                        const point = [data.member.latitude, data.member.longitude];
                        if (!memberMarker) {
                            memberMarker = L.marker(point).addTo(map).bindPopup('Posisi petugas');
                        } else {
                            memberMarker.setLatLng(point);
                        }
                    }
                }

                const latLngs = geometryToLatLngs(data.assignment?.route_geometry);
                if (routeLine) routeLine.remove();
                if (latLngs.length) {
                    routeLine = L.polyline(latLngs, { color: '#ef4444', weight: 5 }).addTo(map);
                    map.fitBounds(routeLine.getBounds(), { padding: [30, 30] });
                }
            }

            refreshTracking();
            setInterval(refreshTracking, 3000);
        </script>
    @endpush
</x-layouts.app>
