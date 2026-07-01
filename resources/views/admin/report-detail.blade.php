<x-layouts.app title="Detail Laporan {{ $report->tracking_code }}">
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
                <div class="mt-5 grid gap-3 md:grid-cols-3">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase text-slate-500">Pelapor</p>
                        <p class="font-black">{{ $report->reporter_name }}</p>
                        <p class="text-sm text-slate-500">{{ $report->reporter_phone }}</p>
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
        </div>

        <aside class="space-y-5">
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
        </script>
    @endpush
</x-layouts.app>
