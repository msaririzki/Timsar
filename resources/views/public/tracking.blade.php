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
                <div class="rounded-xl bg-blue-50 p-4">
                    <p class="text-xs font-black uppercase text-blue-600">Jalur petugas</p>
                    <p id="trailText" class="mt-1 font-black text-blue-900">Belum ada riwayat.</p>
                    <p class="mt-1 text-xs font-semibold text-blue-700">Garis biru menunjukkan jalur yang sudah dilewati.</p>
                </div>
            </div>
        </aside>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="relative">
                <div id="map" class="h-[70vh] min-h-[420px]"></div>
                <div class="pointer-events-none absolute left-3 top-3 z-[500] rounded-xl bg-white/95 p-3 text-[11px] font-black text-slate-600 shadow-lg backdrop-blur">
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1"><span class="h-1.5 w-5 rounded-full bg-blue-600"></span>Jalur ditempuh</span>
                        <span class="inline-flex items-center gap-1"><span class="h-1.5 w-5 rounded-full bg-red-500"></span>Rute tersisa</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            const reportPoint = [{{ $report->latitude }}, {{ $report->longitude }}];
            const map = L.map('map').setView(reportPoint, 14);
            TimsarMap.addTiles(map);
            const reportMarker = L.marker(reportPoint, { icon: TimsarMap.icon('incident') }).addTo(map).bindPopup('<strong>Lokasi kejadian</strong>');
            let memberMarker = null;
            let memberAccuracyCircle = null;
            let routeLine = null;
            let routeSignature = '';
            let routeFitted = false;
            let trailLines = [];
            let trailSignature = '';

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

                const points = trail?.summary?.point_count ?? 0;
                const distance = trail?.summary?.distance_meters > 0 ? formatDistance(trail.summary.distance_meters) : '0 m';
                document.getElementById('trailText').textContent = points > 0 ? `${distance} terekam dari ${points} titik GPS` : 'Belum ada riwayat.';
            }

            async function refreshTrail() {
                try {
                    const res = await fetch('{{ route('public.tracking.trail', $report->tracking_code) }}', { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;

                    setTrailData(await res.json());
                } catch (error) {
                    //
                }
            }

            async function refreshTracking() {
                const res = await fetch('{{ route('public.tracking.data', $report->tracking_code) }}');
                if (!res.ok) return;

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
                            memberMarker = L.marker(point, { icon: TimsarMap.icon('member') }).addTo(map).bindPopup('<strong>Posisi petugas</strong><br><span class="text-xs text-slate-500">Diperbarui otomatis</span>');
                        } else {
                            TimsarMap.moveMarker(memberMarker, point);
                        }

                        if (data.member.accuracy) {
                            if (!memberAccuracyCircle) {
                                memberAccuracyCircle = L.circle(point, {
                                    radius: data.member.accuracy,
                                    color: '#16a34a',
                                    fillColor: '#22c55e',
                                    fillOpacity: 0.08,
                                    weight: 1,
                                }).addTo(map);
                            } else {
                                memberAccuracyCircle.setLatLng(point);
                                memberAccuracyCircle.setRadius(data.member.accuracy);
                            }
                        }
                    }
                }

                const latLngs = geometryToLatLngs(data.assignment?.route_geometry);
                const signature = JSON.stringify(data.assignment?.route_geometry?.coordinates ?? []);
                if (!latLngs.length) {
                    if (routeLine) {
                        routeLine.remove();
                        routeLine = null;
                    }
                    routeSignature = '';
                    routeFitted = false;
                    return;
                }

                if (signature !== routeSignature) {
                    routeSignature = signature;
                    if (!routeLine) {
                        routeLine = L.polyline(latLngs, TimsarMap.routeOptions()).addTo(map);
                    } else {
                        routeLine.setLatLngs(latLngs);
                    }

                    if (!routeFitted) {
                        map.fitBounds(routeLine.getBounds(), { padding: [30, 30] });
                        routeFitted = true;
                    }
                }
            }

            refreshTracking();
            refreshTrail();
            setInterval(refreshTracking, 3000);
            setInterval(refreshTrail, 5000);
        </script>
    @endpush
</x-layouts.app>
