<x-layouts.app title="Detail Laporan {{ $report->tracking_code }}">
    @php
        $assignment = $report->activeAssignment;
        $memberLocation = $assignment?->member?->memberLocation;
        $memberOnline = $memberLocation?->last_seen_at?->gt(now()->subSeconds(90)) ?? false;
        $trackingUrl = route('public.tracking', $report->tracking_code);
        $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . $report->latitude . ',' . $report->longitude;
        $directionsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . $report->latitude . ',' . $report->longitude;
        $phoneLink = 'tel:' . preg_replace('/[^\d+]/', '', $report->reporter_phone);
        $evidenceSummary = $evidence['summary'];
        $mobileLogs = $evidence['logs'];
        $evidenceUrl = route('admin.reports.evidence', $report);
        $isClosed = in_array($report->status, [\App\Models\Report::STATUS_COMPLETED, \App\Models\Report::STATUS_CANCELLED], true);
        $closedAt = $report->closed_at ?? ($isClosed ? $report->updated_at : null);
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
                padding-left: 2rem;
            }
            .timeline-container::before {
                content: '';
                position: absolute;
                left: 7px;
                top: 8px;
                bottom: 8px;
                width: 2px;
                background-color: #e2e8f0;
            }
            .timeline-item {
                position: relative;
                margin-bottom: 1.75rem;
            }
            .timeline-item:last-child {
                margin-bottom: 0;
            }
            .timeline-dot {
                position: absolute;
                left: -29px;
                top: 4px;
                width: 16px;
                height: 16px;
                border-radius: 9999px;
                background-color: #cbd5e1;
                border: 3px solid #fff;
                box-shadow: 0 0 0 1.5px #cbd5e1;
                transition: all 0.25s ease;
            }
            .timeline-item.active .timeline-dot {
                background-color: #dc2626;
                box-shadow: 0 0 0 1.5px #dc2626;
            }
            @keyframes pulse-ring {
                0% { transform: scale(0.95); opacity: 0.5; }
                50% { transform: scale(1.4); opacity: 0.25; }
                100% { transform: scale(0.95); opacity: 0.5; }
            }
            .timeline-item.active:first-child .timeline-dot::after {
                content: '';
                position: absolute;
                inset: -4px;
                border-radius: 9999px;
                border: 2.5px solid #dc2626;
                animation: pulse-ring 2s infinite ease-in-out;
            }

            /* Details chevron rotation */
            .details-indicator-arrow {
                transition: transform 0.2s ease;
            }
            details[open] .details-indicator-arrow {
                transform: rotate(180deg);
            }

            /* Custom Map Popup */
            .leaflet-popup-content-wrapper {
                border-radius: 0.75rem !important;
                box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05), 0 4px 6px -4px rgba(0,0,0,0.05) !important;
                border: 1px solid #e2e8f0 !important;
                padding: 0.25rem !important;
            }
            .leaflet-popup-content {
                font-family: inherit !important;
                font-size: 0.85rem !important;
                color: #334155 !important;
                line-height: 1.5 !important;
                margin: 0.5rem 0.75rem !important;
            }

            /* Custom Scrollbar for Logs Table */
            .custom-scrollbar::-webkit-scrollbar {
                width: 6px;
                height: 6px;
            }
            .custom-scrollbar::-webkit-scrollbar-track {
                background: #f8fafc;
            }
            .custom-scrollbar::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 99px;
            }
            .custom-scrollbar::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
            }
        </style>
    @endpush

    <section class="space-y-5">

        {{-- ── HEADER COMMAND BAR ── --}}
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-4 md:flex-row md:items-center">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full {{ $isClosed ? 'bg-emerald-600' : 'bg-red-600 animate-pulse' }}"></span>
                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-slate-500">{{ $isClosed ? 'Rekap Operasi TIMSAR' : 'Posko Operasi TIMSAR' }}</span>
                </div>
                <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl flex items-center gap-2.5 flex-wrap">
                    <span class="font-mono text-red-650 bg-red-50/50 border border-red-100 px-2.5 py-0.5 rounded-lg text-xl sm:text-2xl">{{ $report->tracking_code }}</span>
                    <span class="text-slate-800 text-xl sm:text-2xl font-bold">{{ $report->incident_type }}</span>
                </h1>
                <p class="mt-1.5 text-xs sm:text-sm font-medium text-slate-500 flex items-center gap-1.5 flex-wrap">
                    <span>Pelapor: <strong class="text-slate-700">{{ $report->reporter_name }}</strong></span>
                    <span class="text-slate-300">•</span>
                    <span>Masuk: <strong class="text-slate-700">{{ $report->created_at->format('d M Y, H:i') }}</strong></span>
                </p>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap gap-2">
                @unless($isClosed)
                    <a href="{{ $phoneLink }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-red-200 bg-red-50/50 px-3.5 py-2.5 text-xs font-bold text-red-700 transition-all hover:bg-red-100/60 active:scale-95 shadow-sm">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                        </svg>
                        Hubungi
                    </a>
                @endunless
                <a href="{{ $trackingUrl }}" target="_blank" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-700 transition-all hover:bg-slate-50 active:scale-95 shadow-sm">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                    Lacak Publik
                </a>
                <a href="{{ $mapsUrl }}" target="_blank" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-700 transition-all hover:bg-slate-50 active:scale-95 shadow-sm">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                    G-Maps
                </a>
                @unless($isClosed)
                    <a href="{{ $directionsUrl }}" target="_blank" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-700 transition-all hover:bg-slate-50 active:scale-95 shadow-sm">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8m-9-3.75h.008v.008H6V11.25Zm.008 3.75h.008v.008H6V15Zm9.75-6h.008v.008h-.008V9Zm.008 3.75h.008v.008h-.008v-.008Zm9.75-3.75H3.75a1.125 1.125 0 0 0-1.125 1.125v7.5A1.125 1.125 0 0 0 3.75 19.5h16.5a1.125 1.125 0 0 0 1.125-1.125v-7.5A1.125 1.125 0 0 0 20.25 10.5Z" />
                        </svg>
                        Arah Rute
                    </a>
                @endunless
                <a href="{{ $evidenceUrl }}" target="_blank" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50/50 px-3.5 py-2.5 text-xs font-bold text-amber-800 transition-all hover:bg-amber-100/60 active:scale-95 shadow-sm">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    Cetak Bukti
                </a>
                @if($isClosed)
                    <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-xs font-bold text-emerald-700 transition-all hover:bg-emerald-100/70 active:scale-95 shadow-sm">
                        Riwayat
                    </a>
                @endif
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-355 bg-slate-900 hover:bg-slate-800 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition-all active:scale-95">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Dashboard
                </a>
            </div>
        </div>

        {{-- ── TWO COLUMN MAIN PANEL ── --}}
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">

            {{-- Left column --}}
            <div class="flex flex-col gap-4">

                {{-- Detail Laporan --}}
                <div class="order-1 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start border-b border-slate-100 pb-4">
                        <div class="space-y-1">
                            <span class="inline-block text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700 uppercase tracking-wide">ID Kejadian: {{ $report->tracking_code }}</span>
                            <h2 class="text-xl font-black text-slate-950 leading-tight">{{ $report->incident_type }}</h2>
                            <p class="text-sm text-slate-600 leading-relaxed pt-1">{{ $report->description }}</p>
                        </div>
                        <span id="reportStatusBadge" class="inline-flex shrink-0 self-start rounded-full bg-red-50 border border-red-150 px-3.5 py-1 text-xs font-black text-red-700">
                            {{ \App\Http\Controllers\PublicTrackingController::statusLabel($report->status) }}
                        </span>
                    </div>

                    {{-- Form Parameters Grid --}}
                    <div class="mt-4 grid gap-3 grid-cols-2 sm:grid-cols-4">
                        <div class="rounded-xl bg-slate-50 p-4 border border-slate-100 flex flex-col justify-between">
                            <div>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-1">Pelapor</span>
                                <p class="text-sm font-black text-slate-800 truncate">{{ $report->reporter_name }}</p>
                            </div>
                            <p class="text-xs text-slate-500 mt-2 font-medium">{{ $report->reporter_phone }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4 border border-slate-100 flex flex-col justify-between">
                            <div>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-1">Akurasi GPS</span>
                                <p class="text-sm font-black text-slate-800">{{ $report->accuracy ? number_format($report->accuracy) . ' meter' : '-' }}</p>
                            </div>
                            <p class="text-xs text-slate-500 mt-2 font-medium">Radius Deviasi</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4 border border-slate-100 flex flex-col justify-between">
                            <div>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-1">Prioritas</span>
                                <p class="text-sm font-black text-slate-800 flex items-center gap-1.5">
                                    <span class="inline-block h-2 w-2 rounded-full {{ $report->priority === 'critical' ? 'bg-red-600' : ($report->priority === 'high' ? 'bg-orange-500' : 'bg-yellow-500') }}"></span>
                                    {{ strtoupper($report->priority) }}
                                </p>
                            </div>
                            <p class="text-xs text-slate-500 mt-2 font-medium">Tingkat Darurat</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4 border border-slate-100 flex flex-col justify-between">
                            <div>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-1">Petugas</span>
                                <p class="text-sm font-black text-slate-800 truncate">{{ $report->assignedMember?->name ?? 'Belum ditunjuk' }}</p>
                            </div>
                            <p class="text-xs text-slate-500 mt-2 font-medium">Pelaksana Lapangan</p>
                        </div>
                    </div>

                    @if($isClosed)
                        <div class="mt-4 rounded-xl border {{ $report->status === \App\Models\Report::STATUS_COMPLETED ? 'border-emerald-200 bg-emerald-50/70' : 'border-slate-200 bg-slate-50' }} p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-black text-slate-950">
                                        {{ $report->status === \App\Models\Report::STATUS_COMPLETED ? 'Operasi selesai dan masuk riwayat' : 'Laporan dibatalkan dan masuk riwayat' }}
                                    </p>
                                    <p class="mt-1 text-xs font-semibold text-slate-600">
                                        Ditutup {{ $closedAt?->format('d M Y, H:i') ?? '-' }}
                                        @if($report->closedBy) oleh {{ $report->closedBy->name }} @endif
                                    </p>
                                </div>
                                <a href="{{ $evidenceUrl }}" target="_blank" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-3.5 py-2 text-xs font-bold text-white">
                                    Cetak bukti operasi
                                </a>
                            </div>
                            @if($report->closure_notes)
                                <div class="mt-3 rounded-lg border border-white bg-white/80 p-3 text-xs sm:text-sm text-slate-700">
                                    <span class="font-bold text-slate-900">Catatan penutupan:</span> {{ $report->closure_notes }}
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Map Container --}}
                <div class="order-2 flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <span class="text-sm font-bold text-slate-800">{{ $isClosed ? 'Peta Bukti Perjalanan Petugas' : 'Visual Peta Operasi & Tracking Realtime' }}</span>
                            <div class="mt-1.5 flex flex-wrap gap-3 text-xs font-semibold text-slate-500">
                                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-5 rounded bg-blue-600"></span>Jalur ditempuh</span>
                                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-5 rounded bg-red-500"></span>{{ $isClosed ? 'Rute hasil hitung' : 'Rute navigasi' }}</span>
                                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>Pemancar BTS</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-xs">
                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 shadow-sm">
                                <span class="block text-[9px] font-extrabold uppercase tracking-wider text-slate-400">Jalur GPS</span>
                                <p id="trailDistanceText" class="mt-0.5 font-mono font-black text-slate-800">-</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 shadow-sm">
                                <span class="block text-[9px] font-extrabold uppercase tracking-wider text-slate-400">Titik Log</span>
                                <p id="trailPointText" class="mt-0.5 font-mono font-black text-slate-800">-</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 shadow-sm">
                                <span class="block text-[9px] font-extrabold uppercase tracking-wider text-slate-400">Node BTS</span>
                                <p id="trailNetworkText" class="mt-0.5 font-mono font-black text-slate-800">-</p>
                            </div>
                        </div>
                    </div>
                    <div id="reportMap" class="h-[400px] min-h-[400px] w-full z-10 lg:h-[450px] xl:h-[480px]"></div>
                </div>

                {{-- Bukti Mobile Computing --}}
                <div class="order-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex flex-col gap-2 border-b border-slate-100 pb-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">{{ $isClosed ? 'Rekap Bukti Mobile Computing' : 'Bukti Audit & Telemetri Mobile Computing' }}</h2>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ $isClosed ? 'Bukti perjalanan yang tersimpan selama operasi berlangsung.' : 'Ringkasan perpindahan lokasi, observasi menara seluler (BTS), dan status GPS petugas.' }}
                            </p>
                        </div>
                        <span id="evidenceLastSeenText" class="rounded-full bg-slate-100 border border-slate-200 px-3 py-1 text-xs font-black text-slate-700">
                            {{ $evidenceSummary['last_at'] ? 'Update ' . $evidenceSummary['last_at']->format('H:i:s') : 'Belum ada ping' }}
                        </span>
                    </div>

                    {{-- Telemetry Dashboard Cards --}}
                    <div class="mt-4 grid gap-3 grid-cols-2 lg:grid-cols-5">
                        <div class="rounded-xl border border-blue-100 bg-blue-50/50 p-3.5 flex flex-col justify-between">
                            <span class="block text-[10px] font-extrabold uppercase tracking-wider text-blue-600">Titik GPS</span>
                            <p id="evidenceGpsText" class="mt-2 text-2xl font-mono font-black text-blue-900">{{ number_format($evidenceSummary['gps_points']) }}</p>
                        </div>
                        <div class="rounded-xl border border-amber-100 bg-amber-50/50 p-3.5 flex flex-col justify-between">
                            <span class="block text-[10px] font-extrabold uppercase tracking-wider text-amber-700">Log BTS</span>
                            <p id="evidenceCellText" class="mt-2 text-2xl font-mono font-black text-amber-900">{{ number_format($evidenceSummary['cell_observations']) }}</p>
                        </div>
                        <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-3.5 flex flex-col justify-between">
                            <span class="block text-[10px] font-extrabold uppercase tracking-wider text-emerald-700">Pindah Jaringan</span>
                            <p id="evidenceNetworkText" class="mt-2 text-2xl font-mono font-black text-emerald-900">{{ number_format($evidenceSummary['network_changes']) }}x</p>
                        </div>
                        <div class="rounded-xl border border-orange-100 bg-orange-50/50 p-3.5 flex flex-col justify-between">
                            <span class="block text-[10px] font-extrabold uppercase tracking-wider text-orange-700">Handover BTS</span>
                            <p id="evidenceHandoverText" class="mt-2 text-2xl font-mono font-black text-orange-900">{{ number_format($evidenceSummary['handovers']) }}x</p>
                        </div>
                        <div class="rounded-xl border border-slate-205 bg-slate-50 p-3.5 flex flex-col justify-between">
                            <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-550">Jalur Terekam</span>
                            <p id="evidenceDistanceText" class="mt-2 text-xl font-mono font-black text-slate-800">
                                {{ $evidenceSummary['distance_meters'] >= 1000 ? number_format($evidenceSummary['distance_meters'] / 1000, 2) . ' km' : number_format($evidenceSummary['distance_meters']) . ' m' }}
                            </p>
                        </div>
                    </div>

                    {{-- Cell info box --}}
                    <div class="mt-4 grid gap-3 lg:grid-cols-2">
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3.5 flex items-center gap-3">
                            <div class="p-2 rounded-lg bg-white border border-slate-200 text-slate-400 shrink-0">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75Z" />
                                </svg>
                            </div>
                            <div>
                                <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Menara BTS Awal</span>
                                <p id="evidenceFirstCellText" class="mt-0.5 text-xs sm:text-sm font-extrabold text-slate-850">
                                    @if($evidenceSummary['first_cell'])
                                        {{ $evidenceSummary['first_cell']['operator'] }} {{ $evidenceSummary['first_cell']['radio_type'] }} / Cell {{ $evidenceSummary['first_cell']['cell_id'] }}
                                    @else
                                        Belum ada data BTS
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3.5 flex items-center gap-3">
                            <div class="p-2 rounded-lg bg-white border border-slate-200 text-slate-450 shrink-0">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.284 16.284A3 3 0 0 0 12 21v-4.25m3.716-.466A3 3 0 0 1 12 21v-4.25m0 0a3.75 3.75 0 1 0-3.75-3.75H12v3.75Z" />
                                </svg>
                            </div>
                            <div>
                                <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Menara BTS Terbaru</span>
                                <p id="evidenceLatestCellText" class="mt-0.5 text-xs sm:text-sm font-extrabold text-slate-855">
                                    @if($evidenceSummary['latest_cell'])
                                        {{ $evidenceSummary['latest_cell']['operator'] }} {{ $evidenceSummary['latest_cell']['radio_type'] }} / Cell {{ $evidenceSummary['latest_cell']['cell_id'] }}
                                    @else
                                        Belum ada data BTS
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bukti BTS details --}}
                <details open class="order-4 rounded-xl border border-amber-250 bg-white p-4 shadow-sm sm:p-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Log Menara BTS Lapangan</h2>
                            <p class="mt-0.5 text-xs text-slate-500">Daftar serving cell menara seluler Android yang terekam pada perlintasan rute.</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <span id="handoverCountText" class="rounded-lg bg-amber-50 border border-amber-100 px-2.5 py-1 text-xs font-black text-amber-800">
                                0 titik BTS
                            </span>
                            <svg class="details-indicator-arrow h-4 w-4 text-slate-550" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </summary>
                    <div id="handoverTimeline" class="mt-4 space-y-2 border-t border-slate-100 pt-4 max-h-[300px] overflow-y-auto custom-scrollbar pr-1">
                        <p class="rounded-lg bg-slate-50 p-4 text-center text-xs text-slate-550">Belum ada data BTS dari aplikasi Android anggota.</p>
                    </div>
                </details>

                {{-- Log Table --}}
                <details class="order-5 rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-4 sm:px-5">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Data Mentah Log Telemetri GPS & Jaringan</h2>
                            <p class="mt-0.5 text-xs text-slate-500">
                                <span id="mobileLogCountText" class="font-semibold text-slate-700">Menampilkan {{ $mobileLogs->count() }} log terbaru</span>. {{ $isClosed ? 'Data arsip operasi dari perangkat Android.' : 'Live feed dari perangkat Android.' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="hidden sm:inline-flex items-center gap-1 rounded-full {{ $isClosed ? 'bg-slate-50 text-slate-600 border-slate-200' : 'bg-emerald-50 text-emerald-700 border-emerald-100' }} px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wide border">
                                @unless($isClosed)<span class="h-1 w-1 rounded-full bg-emerald-500 animate-pulse"></span>@endunless {{ $isClosed ? 'Arsip' : 'Auto Refresh' }}
                            </span>
                            <svg class="details-indicator-arrow h-4 w-4 text-slate-550" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </summary>
                    <div class="max-h-[380px] overflow-auto border-t border-slate-100 custom-scrollbar">
                        <table class="min-w-full divide-y divide-slate-100 text-left text-xs sm:text-sm">
                            <thead class="sticky top-0 z-10 bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500 shadow-sm border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3 font-extrabold">Waktu</th>
                                    <th class="px-4 py-3 font-extrabold">Koordinat GPS & Akurasi</th>
                                    <th class="px-4 py-3 font-extrabold">Jaringan</th>
                                    <th class="px-4 py-3 font-extrabold">Menara Seluler (BTS)</th>
                                    <th class="px-4 py-3 font-extrabold">Kekuatan Sinyal</th>
                                </tr>
                            </thead>
                            <tbody id="mobileLogTableBody" class="divide-y divide-slate-100 bg-white font-medium text-slate-750">
                                @forelse($mobileLogs as $log)
                                    <tr class="align-top hover:bg-slate-50/50 transition-colors">
                                        <td class="whitespace-nowrap px-4 py-3 font-bold text-slate-900">{{ $log['recorded_at']?->format('H:i:s') }}<br><span class="font-normal text-[10px] text-slate-500">{{ $log['recorded_at']?->format('d M Y') }}</span></td>
                                        <td class="px-4 py-3 font-mono text-slate-700 leading-normal">
                                            {{ number_format($log['latitude'], 6) }}, {{ number_format($log['longitude'], 6) }}
                                            <br><span class="font-sans text-[10px] text-slate-500">Akurasi {{ $log['accuracy'] !== null ? number_format($log['accuracy']) . ' m' : '-' }}</span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 font-extrabold text-slate-800">{{ strtoupper($log['network_type']) }}</td>
                                        <td class="px-4 py-3 text-slate-700 leading-normal">
                                            @if($log['cell'])
                                                <span class="font-black text-amber-900">{{ $log['cell']['operator'] }} {{ $log['cell']['radio_type'] }}</span>
                                                <br><span class="font-mono text-[10px]">Cell {{ $log['cell']['cell_id'] }}</span>
                                                <br><span class="text-[10px] text-slate-500">TAC/LAC {{ $log['cell']['tac_or_lac'] ?? '-' }} - PCI {{ $log['cell']['pci_or_psc'] ?? '-' }}</span>
                                            @else
                                                <span class="text-slate-400 italic">Tidak terdeteksi</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 font-mono text-slate-700 leading-normal">
                                            @if($log['signal'])
                                                RSRP {{ $log['signal']['rsrp_dbm'] ?? '-' }} dBm<br>
                                                <span class="text-[10px] text-slate-500">RSRQ {{ $log['signal']['rsrq_db'] ?? '-' }} / SINR {{ $log['signal']['sinr_db'] ?? '-' }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-xs font-semibold text-slate-500">Belum ada log mobile computing dari petugas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </details>

                {{-- Timeline Penanganan --}}
                <details class="order-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-bold text-slate-900">
                        <span>Timeline Log Aktivitas Penanganan</span>
                        <svg class="details-indicator-arrow h-4 w-4 text-slate-550" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </summary>
                    <div class="mt-4 border-t border-slate-100 pt-4 timeline-container">
                        @forelse($timeline as $item)
                            <div class="timeline-item active">
                                <span class="timeline-dot"></span>
                                <div class="ml-3">
                                    <p class="font-black text-slate-800 text-sm leading-snug">{{ $item['label'] }}</p>
                                    <p class="text-xs text-slate-500 mt-1 font-medium">{{ $item['time']->format('d M Y H:i') }} • Penanggung Jawab: <span class="font-bold text-slate-700">{{ $item['note'] }}</span></p>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 text-center py-2">Belum ada aktivitas terekam.</p>
                        @endforelse
                    </div>
                </details>
            </div>

            {{-- Right column (Sidebar) --}}
            <aside class="space-y-4 xl:sticky xl:top-4 xl:self-start">

                @if($isClosed)
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900 border-b border-emerald-100 pb-3">Rekap Penutupan Operasi</h2>
                    <div class="mt-3.5 space-y-3">
                        <div class="rounded-xl bg-white p-4 border border-emerald-100">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-700">Status Akhir</span>
                            <p class="mt-1 text-lg font-black text-slate-950">{{ \App\Http\Controllers\PublicTrackingController::statusLabel($report->status) }}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ $closedAt?->format('d M Y, H:i') ?? '-' }}</p>
                        </div>

                        <div class="rounded-xl bg-white p-4 border border-emerald-100">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Petugas Penanganan</span>
                            <p class="mt-1 text-sm font-black text-slate-850">{{ $assignment?->member?->name ?? $report->assignedMember?->name ?? '-' }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $assignment?->member?->phone ?? $report->assignedMember?->phone ?? '-' }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-2.5">
                            <div class="rounded-xl bg-white p-3.5 border border-emerald-100">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Jalur Bukti</span>
                                <p class="mt-1 font-mono text-sm font-black text-slate-900">{{ $evidenceSummary['distance_meters'] >= 1000 ? number_format($evidenceSummary['distance_meters'] / 1000, 2) . ' km' : number_format($evidenceSummary['distance_meters']) . ' m' }}</p>
                            </div>
                            <div class="rounded-xl bg-white p-3.5 border border-emerald-100">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Titik GPS</span>
                                <p class="mt-1 font-mono text-sm font-black text-slate-900">{{ number_format($evidenceSummary['gps_points']) }}</p>
                            </div>
                            <div class="rounded-xl bg-white p-3.5 border border-emerald-100">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Log BTS</span>
                                <p class="mt-1 font-mono text-sm font-black text-slate-900">{{ number_format($evidenceSummary['cell_observations']) }}</p>
                            </div>
                            <div class="rounded-xl bg-white p-3.5 border border-emerald-100">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Pindah Jaringan</span>
                                <p class="mt-1 font-mono text-sm font-black text-slate-900">{{ number_format($evidenceSummary['network_changes']) }}x</p>
                            </div>
                        </div>

                        <a href="{{ $evidenceUrl }}" target="_blank" class="flex items-center justify-center rounded-lg bg-slate-900 py-3 text-xs sm:text-sm font-bold text-white transition-all shadow-md active:scale-95">
                            Cetak Bukti Operasi
                        </a>
                        <a href="{{ route('admin.reports.index') }}" class="flex items-center justify-center rounded-lg border border-emerald-200 bg-white py-3 text-xs sm:text-sm font-bold text-emerald-700 transition-all active:scale-95">
                            Buka Riwayat Laporan
                        </a>
                    </div>
                </div>
                @else
                {{-- Monitoring Petugas --}}
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center justify-between">
                        <span>Petugas Lapangan</span>
                        @if($assignment?->member)
                            <span class="inline-flex h-2 w-2 rounded-full bg-red-650 animate-pulse"></span>
                        @endif
                    </h2>
                    @if($assignment?->member)
                        <div class="mt-3.5 space-y-3">
                            <div class="rounded-xl bg-slate-50 p-4 border border-slate-100 flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-slate-200 border border-slate-300 flex items-center justify-center font-black text-slate-700 text-sm uppercase shrink-0">
                                    {{ substr($assignment->member->name, 0, 2) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-black text-slate-850 truncate leading-tight">{{ $assignment->member->name }}</p>
                                    <p class="text-xs text-slate-500 mt-1">{{ $assignment->member->phone }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2.5">
                                <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-100">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-0.5">Status Tugas</span>
                                    <p id="assignmentStatusText" class="text-xs sm:text-sm font-black text-slate-800 leading-tight">
                                        {{ \App\Http\Controllers\PublicTrackingController::assignmentLabel($assignment->status) }}
                                    </p>
                                </div>
                                <div class="rounded-xl {{ $memberOnline ? 'bg-emerald-50 border border-emerald-150' : 'bg-slate-50' }} p-3.5 flex flex-col justify-between">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-0.5">Koneksi GPS</span>
                                    <p id="memberOnlineText" class="text-xs sm:text-sm font-black leading-none {{ $memberOnline ? 'text-emerald-700' : 'text-slate-500' }}">
                                        {{ $memberOnline ? 'Online' : 'Offline' }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2.5">
                                <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-100">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-0.5">Sisa Jarak</span>
                                    <p id="assignmentDistanceText" class="text-xs sm:text-sm font-mono font-black text-slate-800 leading-tight">
                                        {{ $assignment->distance_meters ? number_format($assignment->distance_meters / 1000, 2) . ' km' : '-' }}
                                    </p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-100">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-0.5">Estimasi ETA</span>
                                    <p id="assignmentDurationText" class="text-xs sm:text-sm font-mono font-black text-slate-850 leading-tight">
                                        {{ $assignment->duration_seconds ? round($assignment->duration_seconds / 60) . ' menit' : '-' }}
                                    </p>
                                </div>
                            </div>

                            <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-100">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-0.5">Pembaruan GPS</span>
                                <p id="memberLastSeenText" class="text-xs sm:text-sm font-black text-slate-800">
                                    {{ $memberLocation?->last_seen_at?->diffForHumans() ?? '-' }}
                                </p>
                                <p id="memberGpsMetaText" class="text-[10px] text-slate-500 mt-1 font-mono leading-tight">
                                    {{ $memberLocation?->network_type ?? 'unknown' }}{{ $memberLocation?->accuracy ? ' - akurasi ' . number_format($memberLocation->accuracy) . ' m' : '' }}
                                </p>
                            </div>

                            <a href="tel:{{ preg_replace('/[^\d+]/', '', $assignment->member->phone) }}" class="flex items-center justify-center gap-1.5 w-full rounded-lg bg-slate-900 hover:bg-slate-800 py-3 text-xs sm:text-sm font-bold text-white transition-all shadow-md hover:shadow-lg active:scale-95">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                </svg>
                                Hubungi Petugas
                            </a>
                            @unless($isClosed)
                                <form method="POST" action="{{ route('admin.reports.cancel-assignment', $report) }}" onsubmit="return confirm('Batalkan petugas yang sedang ditugaskan? Laporan tetap aktif dan bisa ditugaskan ulang.');">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center justify-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 py-3 text-xs font-bold text-amber-800 transition-all hover:bg-amber-100 active:scale-95">
                                        Batalkan Petugas
                                    </button>
                                    <p class="mt-1.5 text-[10px] font-semibold text-amber-700">Menghentikan alarm petugas dan membuka penugasan ulang.</p>
                                </form>
                            @endunless
                        </div>
                    @else
                        <p class="mt-3.5 rounded-xl bg-slate-50 border border-slate-100 p-5 text-xs text-slate-500 text-center font-medium">Belum ada petugas ditugaskan untuk laporan ini.</p>
                    @endif
                </div>
                @endif

                @unless(in_array($report->status, [\App\Models\Report::STATUS_COMPLETED, \App\Models\Report::STATUS_CANCELLED], true))
                {{-- Anggota Terdekat --}}
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">Rekomendasi Anggota Terdekat</h2>
                    <p class="text-xs text-slate-400 mt-1.5">Radius pencarian dihitung otomatis dari koordinat GPS terakhir anggota.</p>
                    <div class="mt-4 space-y-3">
                        @forelse($nearestMembers as $member)
                            <form method="POST" action="{{ route('admin.reports.assign-member', $report) }}" class="rounded-xl border border-slate-200 p-4 bg-white hover:bg-slate-50 transition-all shadow-sm">
                                @csrf
                                <input type="hidden" name="member_id" value="{{ $member->id }}">
                                <div class="flex items-start justify-between gap-2.5">
                                    <div class="min-w-0">
                                        <p class="font-black text-slate-900 text-sm truncate leading-tight">{{ $member->name }}</p>
                                        <p class="text-xs text-slate-500 mt-1.5 font-mono">
                                            {{ strtoupper($member->network_type) }} • {{ number_format($member->distance_meters) }} meter
                                        </p>
                                    </div>
                                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wide {{ $member->is_online ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $member->is_online ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}"></span>
                                        {{ $member->is_online ? 'Online' : 'Offline' }}
                                    </span>
                                </div>
                                <button type="submit" class="mt-3.5 w-full rounded-lg bg-slate-900 hover:bg-slate-800 py-2.5 text-center text-xs font-bold text-white transition-all active:scale-95 shadow-sm">
                                    Tugaskan Anggota
                                </button>
                            </form>
                        @empty
                            <p class="rounded-xl bg-slate-50 border border-slate-100 p-5 text-xs text-slate-500 text-center font-medium">Belum ada anggota dengan lokasi aktif.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Batalkan Laporan --}}
                <form method="POST" action="{{ route('admin.reports.cancel', $report) }}" class="rounded-xl border border-red-200 bg-red-50/40 p-4 space-y-3">
                    @csrf
                    <div>
                        <h2 class="font-bold text-red-950 text-sm">Batalkan Laporan</h2>
                        <p class="text-xs text-red-750 mt-1">Gunakan bila terkonfirmasi laporan palsu atau evakuasi batal dilakukan.</p>
                    </div>
                    <div>
                        <label for="closure_notes" class="block text-xs font-bold text-red-950 uppercase tracking-wide">Alasan Pembatalan <span class="text-red-600">*</span></label>
                        <textarea id="closure_notes" name="closure_notes" rows="3" minlength="10" maxlength="500" required class="mt-1.5 w-full rounded-lg border border-red-200 bg-white px-3 py-2 text-xs sm:text-sm text-slate-900 outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100 placeholder:text-slate-400" placeholder="Jelaskan alasan detail pembatalan laporan disini..."></textarea>
                        @error('closure_notes')
                            <p class="mt-1 text-xs font-semibold text-red-700">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-red-600 hover:bg-red-700 py-2.5 text-center text-xs font-bold text-white shadow-sm transition-all active:scale-95">
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
            const mobileLogUrl = @json($assignment ? route('admin.assignments.mobile-log', $assignment) : null);
            const reportIsClosed = @json($isClosed);
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
            let cellMarkers = [];

            @if($report->activeAssignment?->member?->memberLocation)
                const memberPoint = [{{ $report->activeAssignment->member->memberLocation->latitude }}, {{ $report->activeAssignment->member->memberLocation->longitude }}];
                memberMarker = L.marker(memberPoint, { icon: TimsarMap.icon('member') }).addTo(map).bindPopup('<strong>{{ $isClosed ? 'Posisi terakhir petugas' : 'Petugas ditugaskan' }}</strong>');
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
                cellMarkers.forEach((marker) => marker.remove());
                cellMarkers = [];
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

            function signalLabel(point) {
                const values = [];
                if (point.rsrp_dbm !== null && point.rsrp_dbm !== undefined) values.push(`RSRP ${point.rsrp_dbm} dBm`);
                if (point.signal_dbm !== null && point.signal_dbm !== undefined) values.push(`Sinyal ${point.signal_dbm} dBm`);
                if (point.accuracy !== null && point.accuracy !== undefined) values.push(`GPS ${Math.round(point.accuracy)} m`);
                return values.length ? values.join(' - ') : 'Detail sinyal belum tersedia';
            }

            function localTime(value) {
                if (!value) return '-';
                return new Date(value).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }

            function localDate(value) {
                if (!value) return '-';
                return new Date(value).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            }

            function distanceText(meters) {
                const value = Number(meters || 0);
                return value >= 1000 ? `${(value / 1000).toFixed(2)} km` : `${Math.round(value)} m`;
            }

            function evidenceCellText(cell) {
                if (!cell) return 'Belum ada data BTS';
                return `${cell.operator || 'Operator'} ${cell.radio_type || 'CELL'} / Cell ${cell.cell_id || '-'}`;
            }

            function renderMobileLogs(logs) {
                const body = document.getElementById('mobileLogTableBody');
                if (!body) return;

                document.getElementById('mobileLogCountText')?.replaceChildren(
                    document.createTextNode(`Menampilkan ${logs?.length ?? 0} log terbaru`)
                );

                if (!logs?.length) {
                    body.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-sm font-semibold text-slate-500">Belum ada log mobile computing dari petugas.</td></tr>';
                    return;
                }

                body.innerHTML = logs.map((log) => {
                    const cell = log.cell;
                    const signal = log.signal;
                    return `
                        <tr class="align-top hover:bg-slate-50/50 transition-colors">
                            <td class="whitespace-nowrap px-4 py-3 font-bold text-slate-800">${escapeHtml(localTime(log.recorded_at_iso))}<br><span class="font-normal text-[10px] text-slate-500">${escapeHtml(localDate(log.recorded_at_iso))}</span></td>
                            <td class="px-4 py-3 font-mono text-slate-700 leading-normal">
                                ${Number(log.latitude).toFixed(6)}, ${Number(log.longitude).toFixed(6)}
                                <br><span class="font-sans text-[10px] text-slate-550">Akurasi ${log.accuracy !== null && log.accuracy !== undefined ? Math.round(log.accuracy) + ' m' : '-'}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-extrabold text-slate-800">${escapeHtml(String(log.network_type || 'unknown').toUpperCase())}</td>
                            <td class="px-4 py-3 text-slate-700 leading-normal">
                                ${cell ? `
                                    <span class="font-black text-amber-900">${escapeHtml(cell.operator || 'Operator')} ${escapeHtml(cell.radio_type || 'CELL')}</span>
                                    <br><span class="font-mono text-[10px]">Cell ${escapeHtml(cell.cell_id || '-')}</span>
                                    <br><span class="text-[10px] text-slate-500 font-medium">TAC/LAC ${escapeHtml(cell.tac_or_lac || '-')} - PCI ${escapeHtml(cell.pci_or_psc || '-')}</span>
                                ` : '<span class="text-slate-400 italic">Tidak tersedia</span>'}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-slate-700 leading-normal">
                                ${signal ? `RSRP ${escapeHtml(signal.rsrp_dbm ?? '-')} dBm<br><span class="text-[10px] text-slate-500">RSRQ ${escapeHtml(signal.rsrq_db ?? '-')} / SINR ${escapeHtml(signal.sinr_db ?? '-')}</span>` : '-'}
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            function setEvidenceSummary(summary) {
                if (!summary) return;

                document.getElementById('evidenceGpsText')?.replaceChildren(document.createTextNode(Number(summary.gps_points || 0).toLocaleString('id-ID')));
                document.getElementById('evidenceCellText')?.replaceChildren(document.createTextNode(Number(summary.cell_observations || 0).toLocaleString('id-ID')));
                document.getElementById('evidenceNetworkText')?.replaceChildren(document.createTextNode(`${Number(summary.network_changes || 0).toLocaleString('id-ID')}x`));
                document.getElementById('evidenceHandoverText')?.replaceChildren(document.createTextNode(`${Number(summary.handovers || 0).toLocaleString('id-ID')}x`));
                document.getElementById('evidenceDistanceText')?.replaceChildren(document.createTextNode(distanceText(summary.distance_meters)));
                document.getElementById('evidenceFirstCellText')?.replaceChildren(document.createTextNode(evidenceCellText(summary.first_cell)));
                document.getElementById('evidenceLatestCellText')?.replaceChildren(document.createTextNode(evidenceCellText(summary.latest_cell)));
                document.getElementById('evidenceLastSeenText')?.replaceChildren(document.createTextNode(summary.last_at ? `Update ${localTime(summary.last_at)}` : 'Belum ada ping'));
            }

            function setTrailData(trail) {
                const signature = JSON.stringify([trail?.segments ?? [], trail?.handovers ?? [], trail?.cell_points ?? []]);
                if (signature === trailSignature) return;

                trailSignature = signature;
                clearTrailLines();

                (trail?.segments ?? []).forEach((segment) => {
                    const latLngs = (segment.points ?? []).map((point) => [point.latitude, point.longitude]);
                    if (latLngs.length < 2) return;

                    trailLines.push(L.polyline(latLngs, TimsarMap.trailOptions()).addTo(map));
                });

                (trail?.cell_points ?? []).forEach((point) => {
                    const isFirst = point.event === 'first';
                    const marker = L.marker([point.latitude, point.longitude], {
                        icon: TimsarMap.icon('cell', { pulse: false }),
                    }).addTo(map).bindPopup(`
                        <strong>${isFirst ? 'BTS awal terekam' : 'BTS berubah'}</strong><br>
                        <span class="text-xs">${escapeHtml(cellLabel(point.cell))}</span><br>
                        <span class="text-xs text-slate-500">${escapeHtml(signalLabel(point))}</span><br>
                        <span class="text-xs text-slate-500">${escapeHtml(new Date(point.observed_at).toLocaleString('id-ID'))}</span>
                    `);
                    cellMarkers.push(marker);
                });

                const pointCount = trail?.summary?.point_count ?? 0;
                const travelled = pointCount > 0
                    ? (trail.summary.distance_meters > 0 ? formatDistance(trail.summary.distance_meters) : '0 m')
                    : '-';
                document.getElementById('trailDistanceText')?.replaceChildren(document.createTextNode(travelled));
                document.getElementById('trailPointText')?.replaceChildren(document.createTextNode(`${pointCount} titik`));
                const handovers = trail?.handovers ?? [];
                const cellPoints = trail?.cell_points ?? [];
                document.getElementById('trailNetworkText')?.replaceChildren(document.createTextNode(`${handovers.length}x BTS`));
                document.getElementById('handoverCountText').textContent = `${cellPoints.length} titik BTS / ${handovers.length} handover`;
                document.getElementById('handoverTimeline').innerHTML = cellPoints.length
                    ? cellPoints.slice().reverse().map((point) => `
                        <button type="button" class="w-full rounded-xl border border-amber-100 bg-amber-50/40 p-3.5 text-left hover:bg-amber-50 transition-all hover:border-amber-200" data-cell-lat="${point.latitude}" data-cell-lng="${point.longitude}">
                            <span class="block text-xs font-black text-amber-900">${point.event === 'first' ? 'BTS Awal' : 'BTS Berubah'} - ${escapeHtml(cellLabel(point.cell))}</span>
                            <span class="mt-1 block text-xs text-slate-600 font-medium">${escapeHtml(new Date(point.observed_at).toLocaleString('id-ID'))} • ${escapeHtml(signalLabel(point))}</span>
                            <span class="mt-1.5 block font-mono text-[10px] text-slate-450">Koordinat: ${Number(point.latitude).toFixed(5)}, ${Number(point.longitude).toFixed(5)}</span>
                        </button>
                    `).join('')
                    : '<p class="rounded-lg bg-slate-50 p-4 text-center text-xs text-slate-550">Belum ada data BTS dari aplikasi Android anggota.</p>';

                document.querySelectorAll('[data-cell-lat]').forEach((button) => {
                    button.addEventListener('click', () => map.setView([
                        Number(button.dataset.cellLat),
                        Number(button.dataset.cellLng),
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

            async function refreshMobileLog() {
                if (!mobileLogUrl) return;

                try {
                    const res = await fetch(mobileLogUrl, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;

                    const data = await res.json();
                    setEvidenceSummary(data.summary);
                    renderMobileLogs(data.logs);
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

            refreshTrail();
            refreshMobileLog();
            if (!reportIsClosed) {
                refreshReportDetail();
                setInterval(refreshReportDetail, 3000);
                setInterval(refreshTrail, 5000);
                setInterval(refreshMobileLog, 10000);
            }
        </script>
    @endpush
</x-layouts.app>
