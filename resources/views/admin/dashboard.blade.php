<x-layouts.app title="Dashboard Admin TIMSAR">
    <section class="space-y-5">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <p class="text-sm font-black uppercase text-red-600">Posko TIMSAR</p>
                <h1 class="mt-1 text-3xl font-black md:text-4xl">Dashboard laporan dan anggota aktif</h1>
                <p class="mt-2 text-slate-600">Data peta diperbarui otomatis tiap 5 detik dari server.</p>
            </div>
            <a href="{{ route('public.report') }}" class="rounded-xl bg-red-600 px-4 py-3 text-center font-black text-white">Buka Form Lapor</a>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Laporan baru</p>
                <p class="mt-2 text-3xl font-black">{{ $stats['new'] }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Sedang ditangani</p>
                <p class="mt-2 text-3xl font-black">{{ $stats['active'] }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Anggota online</p>
                <p class="mt-2 text-3xl font-black">{{ $stats['members_online'] }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Selesai hari ini</p>
                <p class="mt-2 text-3xl font-black">{{ $stats['completed_today'] }}</p>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-[1fr_420px]">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-xl font-black">Peta operasional</h2>
                    <p id="mapMeta" class="text-sm text-slate-500">Memuat data peta...</p>
                </div>
                <div id="adminMap" class="h-[620px]"></div>
            </div>

            <aside class="space-y-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-black">Laporan aktif</h2>
                    <div class="mt-4 space-y-3">
                        @forelse($reports as $report)
                            <a href="{{ route('admin.reports.show', $report) }}" class="block rounded-xl border border-slate-200 p-4 hover:border-red-300 hover:bg-red-50">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-black">{{ $report->incident_type }}</p>
                                        <p class="text-sm text-slate-500">{{ $report->tracking_code }}</p>
                                    </div>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black">{{ \App\Http\Controllers\PublicTrackingController::statusLabel($report->status) }}</span>
                                </div>
                            </a>
                        @empty
                            <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Belum ada laporan.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-black">Anggota TIMSAR</h2>
                    <div class="mt-4 space-y-3">
                        @foreach($members as $member)
                            <div class="rounded-xl border border-slate-200 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="font-black">{{ $member->name }}</p>
                                    <span class="rounded-full {{ $member->memberLocation?->last_seen_at?->gt(now()->subSeconds(30)) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }} px-3 py-1 text-xs font-black">
                                        {{ $member->memberLocation?->last_seen_at?->gt(now()->subSeconds(30)) ? 'Online' : 'Offline' }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-slate-500">{{ $member->phone }} - {{ $member->memberLocation?->network_type ?? 'unknown' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>
    </section>

    @push('scripts')
        <script>
            const map = L.map('adminMap').setView([-8.586, 116.1], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
            let markers = [];

            function clearMarkers() {
                markers.forEach((marker) => marker.remove());
                markers = [];
            }

            async function refreshMap() {
                const res = await fetch('{{ route('admin.map-data') }}');
                const data = await res.json();
                clearMarkers();

                data.reports.forEach((report) => {
                    const marker = L.marker([report.latitude, report.longitude]).addTo(map)
                        .bindPopup(`<strong>${report.incident_type}</strong><br>${report.status_label}<br><a href="${report.url}">Buka detail</a>`);
                    markers.push(marker);
                });

                data.members.forEach((member) => {
                    const marker = L.circleMarker([member.latitude, member.longitude], {
                        radius: 9,
                        color: member.is_online ? '#16a34a' : '#64748b',
                        fillColor: member.is_online ? '#22c55e' : '#94a3b8',
                        fillOpacity: .9,
                    }).addTo(map).bindPopup(`<strong>${member.name}</strong><br>${member.network_type}<br>${member.is_online ? 'Online' : 'Offline'}`);
                    markers.push(marker);
                });

                document.getElementById('mapMeta').textContent = `${data.reports.length} laporan aktif, ${data.members.length} anggota terlacak. Update ${new Date().toLocaleTimeString('id-ID')}`;
            }

            refreshMap();
            setInterval(refreshMap, 5000);
        </script>
    @endpush
</x-layouts.app>
