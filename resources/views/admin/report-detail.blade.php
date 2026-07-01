<x-layouts.app title="Detail Laporan {{ $report->tracking_code }}">
    @php
        $assignment = $report->activeAssignment;
        $memberLocation = $assignment?->member?->memberLocation;
        $memberOnline = $memberLocation?->last_seen_at?->gt(now()->subSeconds(90)) ?? false;
        $trackingUrl = route('public.tracking', $report->tracking_code);
        $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . $report->latitude . ',' . $report->longitude;
        $directionsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . $report->latitude . ',' . $report->longitude;
        $phoneLink = 'tel:' . preg_replace('/[^\d+]/', '', $report->reporter_phone);
        $timeline = collect([
            ['label' => 'Laporan masuk', 'time' => $report->created_at, 'note' => $report->reporter_name],
            ['label' => 'Petugas ditugaskan', 'time' => $assignment?->assigned_at, 'note' => $assignment?->member?->name],
            ['label' => 'Tugas diterima', 'time' => $assignment?->accepted_at, 'note' => $assignment?->member?->name],
            ['label' => 'Petugas mulai menuju lokasi', 'time' => $assignment?->started_at, 'note' => $assignment?->member?->name],
            ['label' => 'Petugas sampai lokasi', 'time' => $assignment?->arrived_at, 'note' => $assignment?->member?->name],
            ['label' => 'Laporan selesai', 'time' => $assignment?->completed_at, 'note' => $assignment?->member?->name],
            ['label' => 'Laporan dibatalkan', 'time' => $report->status === \App\Models\Report::STATUS_CANCELLED ? $report->updated_at : null, 'note' => 'Dibatalkan posko'],
        ])->filter(fn ($item) => $item['time']);
    @endphp

    <section class="space-y-5">
        <div class="flex flex-col justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center">
            <div>
                <p class="text-xs font-black uppercase text-slate-500">Detail laporan</p>
                <p class="font-black text-slate-900">{{ $report->tracking_code }}</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-black text-white">
                Kembali ke dashboard
            </a>
        </div>

        <div class="grid gap-3 md:grid-cols-4">
            <a href="{{ $phoneLink }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:border-red-300">
                <p class="text-xs font-black uppercase text-slate-500">Hubungi pelapor</p>
                <p class="mt-1 font-black text-slate-900">{{ $report->reporter_phone }}</p>
            </a>
            <a href="{{ $trackingUrl }}" target="_blank" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:border-red-300">
                <p class="text-xs font-black uppercase text-slate-500">Tracking publik</p>
                <p class="mt-1 font-black text-slate-900">Buka status</p>
            </a>
            <a href="{{ $mapsUrl }}" target="_blank" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:border-red-300">
                <p class="text-xs font-black uppercase text-slate-500">Lokasi kejadian</p>
                <p class="mt-1 font-black text-slate-900">Buka Maps</p>
            </a>
            <a href="{{ $directionsUrl }}" target="_blank" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:border-red-300">
                <p class="text-xs font-black uppercase text-slate-500">Rute cepat</p>
                <p class="mt-1 font-black text-slate-900">Arahkan petugas</p>
            </a>
        </div>

        <div class="grid gap-5 lg:grid-cols-[1fr_420px]">
        <div class="space-y-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col justify-between gap-4 md:flex-row md:items-start">
                    <div>
                        <p class="text-sm font-black uppercase text-red-600">{{ $report->tracking_code }}</p>
                        <h1 class="mt-1 text-3xl font-black">{{ $report->incident_type }}</h1>
                        <p class="mt-2 text-slate-600">{{ $report->description }}</p>
                    </div>
                    <span class="rounded-full bg-red-100 px-4 py-2 text-sm font-black text-red-700">{{ \App\Http\Controllers\PublicTrackingController::statusLabel($report->status) }}</span>
                </div>
                <div class="mt-5 grid gap-3 md:grid-cols-4">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase text-slate-500">Pelapor</p>
                        <p class="font-black">{{ $report->reporter_name }}</p>
                        <p class="text-sm text-slate-500">{{ $report->reporter_phone }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase text-slate-500">Akurasi GPS</p>
                        <p class="font-black">{{ $report->accuracy ? number_format($report->accuracy) . ' m' : '-' }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase text-slate-500">Prioritas</p>
                        <p class="font-black">{{ strtoupper($report->priority) }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase text-slate-500">Petugas</p>
                        <p class="font-black">{{ $report->assignedMember?->name ?? 'Belum ditugaskan' }}</p>
                    </div>
                </div>
                @if($report->assignedMember)
                    <div class="mt-5 flex flex-col gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-black text-emerald-900">Petugas sudah ditugaskan</p>
                            <p class="text-sm font-semibold text-emerald-800">Admin bisa kembali ke dashboard untuk memantau atau membuka kasus lain.</p>
                        </div>
                        <a href="{{ route('admin.dashboard') }}" class="rounded-xl bg-emerald-700 px-4 py-2 text-center text-sm font-black text-white">
                            Ke dashboard
                        </a>
                    </div>
                @endif
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div id="reportMap" class="h-[520px]"></div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-black">Timeline penanganan</h2>
                <div class="mt-4 space-y-3">
                    @forelse($timeline as $item)
                        <div class="flex gap-3 rounded-xl border border-slate-200 p-4">
                            <span class="mt-1 h-3 w-3 shrink-0 rounded-full bg-red-600"></span>
                            <div>
                                <p class="font-black">{{ $item['label'] }}</p>
                                <p class="text-sm text-slate-500">{{ $item['time']->format('d M Y H:i') }} - {{ $item['note'] }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Belum ada aktivitas penanganan.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <aside class="space-y-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-black">Monitoring petugas</h2>
                @if($assignment?->member)
                    <div class="mt-4 space-y-3">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs font-black uppercase text-slate-500">Petugas</p>
                            <p class="font-black">{{ $assignment->member->name }}</p>
                            <p class="text-sm text-slate-500">{{ $assignment->member->phone }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="text-xs font-black uppercase text-slate-500">Status tugas</p>
                                <p class="font-black">{{ \App\Http\Controllers\PublicTrackingController::assignmentLabel($assignment->status) }}</p>
                            </div>
                            <div class="rounded-xl {{ $memberOnline ? 'bg-emerald-50' : 'bg-slate-50' }} p-4">
                                <p class="text-xs font-black uppercase text-slate-500">Koneksi</p>
                                <p class="font-black {{ $memberOnline ? 'text-emerald-700' : 'text-slate-500' }}">{{ $memberOnline ? 'Online' : 'Offline' }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="text-xs font-black uppercase text-slate-500">Jarak</p>
                                <p class="font-black">{{ $assignment->distance_meters ? number_format($assignment->distance_meters / 1000, 2) . ' km' : '-' }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="text-xs font-black uppercase text-slate-500">Estimasi</p>
                                <p class="font-black">{{ $assignment->duration_seconds ? round($assignment->duration_seconds / 60) . ' menit' : '-' }}</p>
                            </div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs font-black uppercase text-slate-500">GPS terakhir</p>
                            <p class="font-black">{{ $memberLocation?->last_seen_at?->diffForHumans() ?? '-' }}</p>
                            <p class="text-sm text-slate-500">{{ $memberLocation?->network_type ?? 'unknown' }}{{ $memberLocation?->accuracy ? ' - akurasi ' . number_format($memberLocation->accuracy) . ' m' : '' }}</p>
                        </div>
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $assignment->member->phone) }}" class="block rounded-xl bg-slate-900 px-4 py-3 text-center font-black text-white">
                            Hubungi petugas
                        </a>
                    </div>
                @else
                    <p class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Belum ada petugas ditugaskan untuk laporan ini.</p>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-black">Anggota terdekat</h2>
                <p class="mt-1 text-sm text-slate-500">Dihitung server dengan Haversine dari lokasi GPS terakhir anggota.</p>
                <div class="mt-4 space-y-3">
                    @forelse($nearestMembers as $member)
                        <form method="POST" action="{{ route('admin.reports.assign-member', $report) }}" class="rounded-xl border border-slate-200 p-4">
                            @csrf
                            <input type="hidden" name="member_id" value="{{ $member->id }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-black">{{ $member->name }}</p>
                                    <p class="text-sm text-slate-500">{{ $member->network_type }} - {{ number_format($member->distance_meters) }} m</p>
                                </div>
                                <span class="rounded-full {{ $member->is_online ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }} px-3 py-1 text-xs font-black">
                                    {{ $member->is_online ? 'Online' : 'Offline' }}
                                </span>
                            </div>
                            <button class="mt-3 w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-black text-white">Tugaskan anggota</button>
                        </form>
                    @empty
                        <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Belum ada anggota dengan lokasi aktif.</p>
                    @endforelse
                </div>
            </div>

            <form method="POST" action="{{ route('admin.reports.cancel', $report) }}" class="rounded-2xl border border-red-200 bg-red-50 p-5">
                @csrf
                <h2 class="font-black text-red-900">Batalkan laporan</h2>
                <p class="mt-1 text-sm text-red-800">Gunakan jika laporan tidak valid atau kejadian sudah tidak membutuhkan respons.</p>
                <button class="mt-4 rounded-xl bg-red-600 px-4 py-2 text-sm font-black text-white">Batalkan</button>
            </form>
        </aside>
        </div>
    </section>

    @push('scripts')
        <script>
            const reportPoint = [{{ $report->latitude }}, {{ $report->longitude }}];
            const map = L.map('reportMap').setView(reportPoint, 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
            L.marker(reportPoint).addTo(map).bindPopup('Lokasi laporan');
            @if($report->activeAssignment?->member?->memberLocation)
                const memberPoint = [{{ $report->activeAssignment->member->memberLocation->latitude }}, {{ $report->activeAssignment->member->memberLocation->longitude }}];
                L.circleMarker(memberPoint, { radius: 9, color: '#16a34a', fillColor: '#22c55e', fillOpacity: .9 }).addTo(map).bindPopup('Petugas ditugaskan');
            @endif
            @if($report->activeAssignment?->route_geometry_json)
                const routeGeometry = @json($report->activeAssignment->route_geometry_json);
                if (routeGeometry?.coordinates?.length) {
                    const routeLine = L.polyline(routeGeometry.coordinates.map((point) => [point[1], point[0]]), {
                        color: '#ef4444',
                        weight: 5,
                    }).addTo(map);
                    map.fitBounds(routeLine.getBounds(), { padding: [30, 30] });
                }
            @endif
        </script>
    @endpush
</x-layouts.app>
