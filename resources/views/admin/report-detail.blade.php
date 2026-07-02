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

    @push('scripts')
        <style>
            /* ── Activity Timeline Styling ── */
            .timeline-container {
                position: relative;
                padding-left: 1.75rem;
            }
            .timeline-container::before {
                content: '';
                position: absolute;
                left: 6px;
                top: 8px;
                bottom: 8px;
                width: 2px;
                background-color: #cbd5e1;
            }
            .timeline-item {
                position: relative;
                margin-bottom: 1.5rem;
            }
            .timeline-item:last-child {
                margin-bottom: 0;
            }
            .timeline-dot {
                position: absolute;
                left: -27px;
                top: 5px;
                width: 14px;
                height: 14px;
                border-radius: 9999px;
                background-color: #cbd5e1;
                border: 3px solid #fff;
                box-shadow: 0 0 0 1.5px #cbd5e1;
                transition: all 0.2s ease;
            }
            .timeline-item.active .timeline-dot {
                background-color: #dc2626;
                box-shadow: 0 0 0 1.5px #dc2626;
            }

            /* Custom Map Popup */
            .leaflet-popup-content-wrapper {
                border-radius: 0.5rem !important;
                box-shadow: 0 4px 15px -3px rgba(0,0,0,0.1) !important;
                border: 1px solid #e2e8f0 !important;
            }
            .leaflet-popup-content {
                font-family: inherit !important;
                font-size: 0.875rem !important;
                color: #334155 !important;
                margin: 0.5rem 0.75rem !important;
            }
        </style>
    @endpush

    <section class="space-y-5">
        
        {{-- ── HEADER COMMAND BAR ── --}}
        <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center pb-3.5 border-b border-slate-200">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-red-600 animate-pulse"></span>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Operasi Penanganan Darurat</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1">
                    Laporan: <span class="font-mono text-red-650">{{ $report->tracking_code }}</span>
                </h1>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Kembali ke Dashboard
            </a>
        </div>

        {{-- ── ACTION CARDS GRID ── --}}
        <div class="grid gap-3 grid-cols-2 md:grid-cols-4">
            <a href="{{ $phoneLink }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-red-350 hover:shadow transition-all flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-red-50 text-red-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Hubungi Pelapor</p>
                    <p class="text-sm sm:text-base font-extrabold text-slate-800 mt-0.5 leading-none">{{ $report->reporter_phone }}</p>
                </div>
            </a>
            <a href="{{ $trackingUrl }}" target="_blank" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-red-350 hover:shadow transition-all flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-blue-50 text-blue-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Tracking Publik</p>
                    <p class="text-sm sm:text-base font-extrabold text-slate-800 mt-0.5 leading-none">Buka Status</p>
                </div>
            </a>
            <a href="{{ $mapsUrl }}" target="_blank" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-red-350 hover:shadow transition-all flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-emerald-50 text-emerald-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Lokasi Kejadian</p>
                    <p class="text-sm sm:text-base font-extrabold text-slate-800 mt-0.5 leading-none">Google Maps</p>
                </div>
            </a>
            <a href="{{ $directionsUrl }}" target="_blank" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-red-350 hover:shadow transition-all flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-purple-50 text-purple-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8m-9-3.75h.008v.008H6V11.25Zm.008 3.75h.008v.008H6V15Zm9.75-6h.008v.008h-.008V9Zm.008 3.75h.008v.008h-.008v-.008Zm9.75-3.75H3.75a1.125 1.125 0 0 0-1.125 1.125v7.5A1.125 1.125 0 0 0 3.75 19.5h16.5a1.125 1.125 0 0 0 1.125-1.125v-7.5A1.125 1.125 0 0 0 20.25 10.5Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Rute Cepat</p>
                    <p class="text-sm sm:text-base font-extrabold text-slate-800 mt-0.5 leading-none">Arah Penyelamatan</p>
                </div>
            </a>
        </div>

        {{-- ── TWO COLUMN MAIN PANEL ── --}}
        <div class="grid gap-4 lg:grid-cols-[1fr_380px]">
            
            {{-- Left column --}}
            <div class="space-y-4">
                
                {{-- Detail Laporan --}}
                <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
                    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start border-b border-slate-100 pb-4">
                        <div>
                            <span class="inline-block text-xs font-mono font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700 uppercase">{{ $report->tracking_code }}</span>
                            <h2 class="mt-2 text-xl sm:text-2xl font-black text-slate-900 leading-tight">{{ $report->incident_type }}</h2>
                            <p class="mt-2 text-sm text-slate-650 leading-relaxed">{{ $report->description }}</p>
                        </div>
                        <span id="reportStatusBadge" class="inline-flex shrink-0 self-start rounded-full bg-red-50 border border-red-200 px-3.5 py-1 text-xs font-black text-red-700">
                            {{ \App\Http\Controllers\PublicTrackingController::statusLabel($report->status) }}
                        </span>
                    </div>

                    {{-- Form Parameters Grid --}}
                    <div class="mt-4 grid gap-3 grid-cols-2 sm:grid-cols-4">
                        <div class="rounded-lg bg-slate-50 p-3.5 border border-slate-100">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Pelapor</span>
                            <p class="text-sm sm:text-base font-extrabold text-slate-800 mt-1 truncate">{{ $report->reporter_name }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $report->reporter_phone }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3.5 border border-slate-100">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Akurasi GPS</span>
                            <p class="text-sm sm:text-base font-extrabold text-slate-800 mt-1">{{ $report->accuracy ? number_format($report->accuracy) . ' m' : '-' }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3.5 border border-slate-100">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Prioritas</span>
                            <p class="text-sm sm:text-base font-extrabold text-slate-800 mt-1">{{ strtoupper($report->priority) }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3.5 border border-slate-100">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Petugas</span>
                            <p class="text-sm sm:text-base font-extrabold text-slate-800 mt-1 truncate">{{ $report->assignedMember?->name ?? 'Belum ada' }}</p>
                        </div>
                    </div>

                    @if($report->assignedMember)
                        <div class="mt-4 flex flex-col gap-3 rounded-lg border border-emerald-200 bg-emerald-50/60 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-bold text-emerald-900">Petugas telah dikerahkan ke lapangan</p>
                                <p class="text-xs text-emerald-700 mt-0.5">Gunakan modul pemantauan di sebelah kanan untuk melacak rute dan posisi GPS secara live.</p>
                            </div>
                            <a href="{{ route('admin.dashboard') }}" class="rounded-lg bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-center text-xs font-bold text-white transition-all shadow-sm">
                                Dashboard &rarr;
                            </a>
                        </div>
                    @endif

                    @if(in_array($report->status, [\App\Models\Report::STATUS_COMPLETED, \App\Models\Report::STATUS_CANCELLED], true))
                        <div class="mt-4 border-l-4 {{ $report->status === \App\Models\Report::STATUS_COMPLETED ? 'border-emerald-500 bg-emerald-50' : 'border-slate-500 bg-slate-50' }} px-4 py-3">
                            <p class="text-sm font-bold text-slate-900">Laporan telah ditutup</p>
                            <p class="mt-1 text-xs text-slate-600">
                                {{ ($report->closed_at ?? $report->updated_at)->format('d M Y, H:i') }}
                                @if($report->closedBy) oleh {{ $report->closedBy->name }} @endif
                            </p>
                            @if($report->closure_notes)
                                <p class="mt-2 text-sm text-slate-700">{{ $report->closure_notes }}</p>
                            @endif
                            <a href="{{ route('admin.reports.index') }}" class="mt-3 inline-flex text-xs font-bold text-slate-800 underline">Kembali ke riwayat laporan</a>
                        </div>
                    @endif
                </div>

                {{-- Map Container --}}
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm flex flex-col">
                    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 flex items-center justify-between">
                        <div>
                            <span class="text-sm font-bold text-slate-800">Peta Operasional Taktis</span>
                            <div class="mt-1 flex flex-wrap gap-2 text-[11px] font-bold text-slate-500">
                                <span class="inline-flex items-center gap-1"><span class="h-1.5 w-5 rounded-full bg-blue-600"></span>Jalur ditempuh</span>
                                <span class="inline-flex items-center gap-1"><span class="h-1.5 w-5 rounded-full bg-red-500"></span>Rute tersisa</span>
                                <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-amber-600"></span>Handover BTS</span>
                            </div>
                        </div>
                        <span class="h-2 w-2 rounded-full bg-red-500 animate-pulse"></span>
                    </div>
                    <div id="reportMap" class="h-[400px] lg:h-[480px] z-10"></div>
                </div>

                <div class="rounded-xl border border-amber-200 bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex items-start justify-between gap-3 border-b border-amber-100 pb-3">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Bukti Perpindahan BTS</h2>
                            <p class="mt-0.5 text-xs text-slate-500">Data serving cell Android yang direkam bersama koordinat GPS petugas.</p>
                        </div>
                        <span id="handoverCountText" class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-black text-amber-800">0 handover</span>
                    </div>
                    <div id="handoverTimeline" class="mt-3 space-y-2">
                        <p class="rounded-lg bg-slate-50 p-4 text-center text-xs text-slate-500">Belum ada data BTS dari aplikasi Android anggota.</p>
                    </div>
                </div>

                {{-- Timeline --}}
                <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Timeline Penanganan Kasus</h2>
                    <div class="mt-4 timeline-container">
                        @forelse($timeline as $item)
                            <div class="timeline-item active">
                                <span class="timeline-dot"></span>
                                <div class="ml-2">
                                    <p class="font-bold text-slate-800 text-xs sm:text-sm">{{ $item['label'] }}</p>
                                    <p class="text-xs text-slate-550 mt-1">{{ $item['time']->format('d M Y H:i') }} • <span class="font-bold text-slate-700">{{ $item['note'] }}</span></p>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-550 text-center py-2">Belum ada aktivitas terekam.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Right column (Sidebar) --}}
            <aside class="space-y-4">
                
                {{-- Monitoring Petugas --}}
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Monitoring Petugas</h2>
                    @if($assignment?->member)
                        <div class="mt-3.5 space-y-3">
                            <div class="rounded-lg bg-slate-50 p-3.5 border border-slate-100">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Petugas Terpilih</span>
                                <p class="text-sm sm:text-base font-extrabold text-slate-800 mt-0.5">{{ $assignment->member->name }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">{{ $assignment->member->phone }}</p>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-2.5">
                                <div class="rounded-lg bg-slate-50 p-3.5 border border-slate-100">
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Status Tugas</span>
                                    <p id="assignmentStatusText" class="text-xs sm:text-sm font-extrabold text-slate-850 mt-0.5">
                                        {{ \App\Http\Controllers\PublicTrackingController::assignmentLabel($assignment->status) }}
                                    </p>
                                </div>
                                <div class="rounded-lg {{ $memberOnline ? 'bg-emerald-50 border border-emerald-100' : 'bg-slate-50' }} p-3.5">
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Koneksi GPS</span>
                                    <p id="memberOnlineText" class="text-xs sm:text-sm font-extrabold mt-0.5 {{ $memberOnline ? 'text-emerald-700' : 'text-slate-500' }}">
                                        {{ $memberOnline ? 'Online' : 'Offline' }}
                                    </p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-2.5">
                                <div class="rounded-lg bg-slate-50 p-3.5 border border-slate-100">
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Jarak</span>
                                    <p id="assignmentDistanceText" class="text-xs sm:text-sm font-extrabold text-slate-850 mt-0.5">
                                        {{ $assignment->distance_meters ? number_format($assignment->distance_meters / 1000, 2) . ' km' : '-' }}
                                    </p>
                                </div>
                                <div class="rounded-lg bg-slate-50 p-3.5 border border-slate-100">
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Estimasi Waktu</span>
                                    <p id="assignmentDurationText" class="text-xs sm:text-sm font-extrabold text-slate-850 mt-0.5">
                                        {{ $assignment->duration_seconds ? round($assignment->duration_seconds / 60) . ' menit' : '-' }}
                                    </p>
                                </div>
                            </div>
                            
                            <div class="rounded-lg bg-slate-50 p-3.5 border border-slate-100">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Ping GPS Terakhir</span>
                                <p id="memberLastSeenText" class="text-xs sm:text-sm font-extrabold text-slate-800 mt-0.5">
                                    {{ $memberLocation?->last_seen_at?->diffForHumans() ?? '-' }}
                                </p>
                                <p id="memberGpsMetaText" class="text-xs text-slate-500 mt-0.5 font-mono">
                                    {{ $memberLocation?->network_type ?? 'unknown' }}{{ $memberLocation?->accuracy ? ' - akurasi ' . number_format($memberLocation->accuracy) . ' m' : '' }}
                                </p>
                            </div>

                            <div class="grid grid-cols-3 gap-2.5">
                                <div class="rounded-lg bg-blue-50 p-3.5 border border-blue-100">
                                    <span class="text-xs font-bold uppercase tracking-wider text-blue-500 block">Ditempuh</span>
                                    <p id="trailDistanceText" class="text-xs sm:text-sm font-extrabold text-blue-800 mt-0.5">-</p>
                                </div>
                                <div class="rounded-lg bg-blue-50 p-3.5 border border-blue-100">
                                    <span class="text-xs font-bold uppercase tracking-wider text-blue-500 block">Titik GPS</span>
                                    <p id="trailPointText" class="text-xs sm:text-sm font-extrabold text-blue-800 mt-0.5">-</p>
                                </div>
                                <div class="rounded-lg bg-blue-50 p-3.5 border border-blue-100">
                                    <span class="text-xs font-bold uppercase tracking-wider text-blue-500 block">Jaringan</span>
                                    <p id="trailNetworkText" class="text-xs sm:text-sm font-extrabold text-blue-800 mt-0.5">-</p>
                                </div>
                            </div>
                            
                            <a href="tel:{{ preg_replace('/[^\d+]/', '', $assignment->member->phone) }}" class="flex items-center justify-center gap-1.5 w-full rounded-lg bg-slate-900 hover:bg-slate-800 py-3 text-xs sm:text-sm font-extrabold text-white transition-colors shadow-sm">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                </svg>
                                Hubungi Petugas
                            </a>
                        </div>
                    @else
                        <p class="mt-3 rounded-lg bg-slate-50 p-4 text-xs text-slate-500 text-center">Belum ada petugas ditugaskan untuk laporan ini.</p>
                    @endif
                </div>

                @unless(in_array($report->status, [\App\Models\Report::STATUS_COMPLETED, \App\Models\Report::STATUS_CANCELLED], true))
                {{-- Anggota Terdekat --}}
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Rekomendasi Anggota Terdekat</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Dihitung otomatis berdasarkan data koordinat GPS terakhir anggota.</p>
                    <div class="mt-3.5 space-y-2.5">
                        @forelse($nearestMembers as $member)
                            <form method="POST" action="{{ route('admin.reports.assign-member', $report) }}" class="rounded-lg border border-slate-200 p-3.5 bg-white hover:bg-slate-50 transition-colors shadow-sm">
                                @csrf
                                <input type="hidden" name="member_id" value="{{ $member->id }}">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="font-bold text-slate-900 text-sm sm:text-base leading-tight">{{ $member->name }}</p>
                                        <p class="text-xs text-slate-500 mt-1 font-mono">
                                            {{ $member->network_type }} • {{ number_format($member->distance_meters) }} meter
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-extrabold uppercase tracking-wide {{ $member->is_online ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $member->is_online ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}"></span>
                                        {{ $member->is_online ? 'Online' : 'Offline' }}
                                    </span>
                                </div>
                                <button type="submit" class="mt-3 w-full rounded-lg bg-slate-900 hover:bg-slate-800 py-2 text-center text-xs sm:text-sm font-bold text-white transition-colors">
                                    Tugaskan Anggota
                                </button>
                            </form>
                        @empty
                            <p class="rounded-lg bg-slate-50 p-4 text-xs text-slate-500 text-center">Belum ada anggota dengan lokasi aktif.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Batalkan Laporan --}}
                <form method="POST" action="{{ route('admin.reports.cancel', $report) }}" class="rounded-xl border border-red-200 bg-red-50/50 p-4">
                    @csrf
                    <h2 class="font-bold text-red-950 text-sm">Batalkan Laporan</h2>
                    <p class="text-xs text-red-700 mt-0.5">Gunakan jika laporan terindikasi palsu, tidak valid, atau evakuasi batal dilakukan.</p>
                    <label for="closure_notes" class="mt-3 block text-xs font-bold text-red-950">Alasan pembatalan</label>
                    <textarea id="closure_notes" name="closure_notes" rows="3" minlength="10" maxlength="500" required class="mt-1 w-full rounded-lg border border-red-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="Contoh: laporan ganda dan sudah dikonfirmasi melalui telepon.">{{ old('closure_notes') }}</textarea>
                    @error('closure_notes')
                        <p class="mt-1 text-xs font-semibold text-red-700">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="mt-3 rounded-lg bg-red-650 hover:bg-red-700 px-4 py-2 text-xs sm:text-sm font-bold text-white shadow-sm transition-colors">
                        Batalkan Laporan
                    </button>
                </form>
                @endunless
            </aside>
        </div>
    </section>

    @push('scripts')
        <script>
            const reportPoint = [{{ $report->latitude }}, {{ $report->longitude }}];
            const trailUrl = @json($assignment ? route('admin.assignments.trail', $assignment) : null);
            const map = L.map('reportMap').setView(reportPoint, 14);
            TimsarMap.addTiles(map);
            L.marker(reportPoint, { icon: TimsarMap.icon('incident') }).addTo(map).bindPopup('<strong>Lokasi laporan</strong>');
            
            let memberMarker = null;
            let memberAccuracyCircle = null;
            let routeLine = null;
            let routeSignature = '';
            let routeFitted = false;
            let trailLines = [];
            let trailSignature = '';
            let handoverMarkers = [];

            @if($report->activeAssignment?->member?->memberLocation)
                const memberPoint = [{{ $report->activeAssignment->member->memberLocation->latitude }}, {{ $report->activeAssignment->member->memberLocation->longitude }}];
                memberMarker = L.marker(memberPoint, { icon: TimsarMap.icon('member') }).addTo(map).bindPopup('<strong>Petugas ditugaskan</strong>');
            @endif
            @if($report->activeAssignment?->route_geometry_json)
                const routeGeometry = @json($report->activeAssignment->route_geometry_json);
                if (routeGeometry?.coordinates?.length) {
                    routeLine = L.polyline(routeGeometry.coordinates.map((point) => [point[1], point[0]]), TimsarMap.routeOptions()).addTo(map);
                    routeSignature = JSON.stringify(routeGeometry.coordinates);
                    map.fitBounds(routeLine.getBounds(), { padding: [30, 30] });
                    routeFitted = true;
                }
            @endif

            function formatDistance(meters) {
                if (!meters) return '-';
                return meters >= 1000 ? `${(meters / 1000).toFixed(2)} km` : `${Math.round(meters)} m`;
            }

            function formatDuration(seconds) {
                if (!seconds) return '-';
                return `${Math.max(1, Math.round(seconds / 60))} menit`;
            }

            function geometryToLatLngs(geometry) {
                if (!geometry || !geometry.coordinates) return [];
                return geometry.coordinates.map((point) => [point[1], point[0]]);
            }

            function clearTrailLines() {
                trailLines.forEach((line) => line.remove());
                trailLines = [];
                handoverMarkers.forEach((marker) => marker.remove());
                handoverMarkers = [];
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function cellLabel(cell) {
                if (!cell) return '-';
                return `${cell.operator || 'Operator'} ${cell.radio_type || 'CELL'} / ${cell.cell_id || '-'}`;
            }

            function setTrailData(trail) {
                const signature = JSON.stringify([trail?.segments ?? [], trail?.handovers ?? []]);
                if (signature === trailSignature) return;

                trailSignature = signature;
                clearTrailLines();

                (trail?.segments ?? []).forEach((segment) => {
                    const latLngs = (segment.points ?? []).map((point) => [point.latitude, point.longitude]);
                    if (latLngs.length < 2) return;

                    trailLines.push(L.polyline(latLngs, TimsarMap.trailOptions()).addTo(map));
                });

                (trail?.handovers ?? []).forEach((handover) => {
                    const marker = L.marker([handover.latitude, handover.longitude], {
                        icon: TimsarMap.icon('cell', { pulse: false }),
                    }).addTo(map).bindPopup(`
                        <strong>Handover BTS</strong><br>
                        <span class="text-xs">${escapeHtml(cellLabel(handover.from))} &rarr; ${escapeHtml(cellLabel(handover.to))}</span><br>
                        <span class="text-xs text-slate-500">${escapeHtml(new Date(handover.observed_at).toLocaleString('id-ID'))}</span>
                    `);
                    handoverMarkers.push(marker);
                });

                const pointCount = trail?.summary?.point_count ?? 0;
                const travelled = pointCount > 0
                    ? (trail.summary.distance_meters > 0 ? formatDistance(trail.summary.distance_meters) : '0 m')
                    : '-';
                document.getElementById('trailDistanceText')?.replaceChildren(document.createTextNode(travelled));
                document.getElementById('trailPointText')?.replaceChildren(document.createTextNode(`${pointCount} titik`));
                document.getElementById('trailNetworkText')?.replaceChildren(document.createTextNode(`${trail?.summary?.network_changes ?? 0}x berubah`));
                const handovers = trail?.handovers ?? [];
                document.getElementById('handoverCountText').textContent = `${handovers.length} handover`;
                document.getElementById('handoverTimeline').innerHTML = handovers.length
                    ? handovers.slice().reverse().map((handover) => `
                        <button type="button" class="w-full rounded-lg border border-amber-100 bg-amber-50/60 p-3 text-left hover:bg-amber-50" data-handover-lat="${handover.latitude}" data-handover-lng="${handover.longitude}">
                            <span class="block text-xs font-black text-amber-900">${escapeHtml(cellLabel(handover.from))} &rarr; ${escapeHtml(cellLabel(handover.to))}</span>
                            <span class="mt-1 block text-xs text-slate-600">${escapeHtml(new Date(handover.observed_at).toLocaleString('id-ID'))} - GPS ${Number(handover.latitude).toFixed(5)}, ${Number(handover.longitude).toFixed(5)}</span>
                        </button>
                    `).join('')
                    : '<p class="rounded-lg bg-slate-50 p-4 text-center text-xs text-slate-500">Belum ada data BTS dari aplikasi Android anggota.</p>';

                document.querySelectorAll('[data-handover-lat]').forEach((button) => {
                    button.addEventListener('click', () => map.setView([
                        Number(button.dataset.handoverLat),
                        Number(button.dataset.handoverLng),
                    ], 17, { animate: true }));
                });
            }

            async function refreshTrail() {
                if (!trailUrl) return;

                try {
                    const res = await fetch(trailUrl, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;

                    setTrailData(await res.json());
                } catch (error) {
                    //
                }
            }

            async function refreshReportDetail() {
                const res = await fetch('{{ route('public.tracking.data', $report->tracking_code) }}', { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;

                const data = await res.json();
                document.getElementById('reportStatusBadge').textContent = data.report.status_label;

                if (data.assignment) {
                    document.getElementById('assignmentStatusText')?.replaceChildren(document.createTextNode(data.assignment.status_label));
                    document.getElementById('assignmentDistanceText')?.replaceChildren(document.createTextNode(formatDistance(data.assignment.distance_meters)));
                    document.getElementById('assignmentDurationText')?.replaceChildren(document.createTextNode(formatDuration(data.assignment.duration_seconds)));
                }

                if (data.member) {
                    const onlineText = document.getElementById('memberOnlineText');
                    if (onlineText) {
                        onlineText.textContent = data.member.is_online ? 'Online' : 'Offline';
                        onlineText.className = `font-black ${data.member.is_online ? 'text-emerald-700' : 'text-slate-500'}`;
                    }

                    document.getElementById('memberLastSeenText')?.replaceChildren(document.createTextNode(
                        data.member.last_seen_at ? new Date(data.member.last_seen_at).toLocaleTimeString('id-ID') : '-'
                    ));
                    document.getElementById('memberGpsMetaText')?.replaceChildren(document.createTextNode(
                        `${data.member.network_type || 'unknown'}${data.member.accuracy ? ' - akurasi ' + Math.round(data.member.accuracy) + ' m' : ''}`
                    ));

                    if (data.member.latitude && data.member.longitude) {
                        const point = [data.member.latitude, data.member.longitude];
                        if (!memberMarker) {
                            memberMarker = L.marker(point, { icon: TimsarMap.icon('member') }).addTo(map).bindPopup('<strong>Petugas ditugaskan</strong>');
                        } else {
                            TimsarMap.moveMarker(memberMarker, point);
                        }

                        if (data.member.accuracy) {
                            if (!memberAccuracyCircle) {
                                memberAccuracyCircle = L.circle(point, {
                                    radius: data.member.accuracy,
                                    color: '#10b981',
                                    fillColor: '#10b981',
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
                const nextSignature = JSON.stringify(data.assignment?.route_geometry?.coordinates ?? []);
                if (latLngs.length && nextSignature !== routeSignature) {
                    routeSignature = nextSignature;
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

            refreshReportDetail();
            refreshTrail();
            setInterval(refreshReportDetail, 3000);
            setInterval(refreshTrail, 5000);
        </script>
    @endpush
</x-layouts.app>
