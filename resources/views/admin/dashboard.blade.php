<x-layouts.app title="Dashboard Admin TIMSAR">

    <section class="space-y-4">
        
        {{-- ── LIGHT COMMAND HEADER ── --}}
        <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center pb-3 border-b border-slate-200">
            <div>
                <div class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-full bg-red-600 animate-pulse"></span>
                    <p class="text-xs font-bold uppercase tracking-wider text-red-600">Pusat Kendali Operasi (Pusko)</p>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900 mt-0.5">Dashboard Koordinasi Realtime</h1>
                <p class="text-sm text-slate-500 mt-0.5">Sistem monitoring laporan darurat dan tracking pergerakan anggota lapangan secara langsung.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.reports.index') }}" class="rounded-lg border border-slate-300 bg-white hover:bg-slate-50 px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition-colors">Riwayat Laporan</a>
                <button id="adminNotificationButton" type="button" class="rounded-lg border border-slate-300 bg-white hover:bg-slate-50 px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition-colors">
                    Aktifkan notifikasi
                </button>
                <button id="stopAdminAlarmButton" type="button" class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-xs font-bold text-red-700 shadow-sm transition-colors hover:bg-red-100">
                    Hentikan alarm
                </button>
            </div>
        </div>

        {{-- ── DIGITAL STATS BAR ── --}}
        <div class="grid gap-3 grid-cols-2 md:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Laporan Baru</span>
                    <p class="text-3xl sm:text-4xl font-black text-red-600 mt-1 leading-none">{{ $stats['new'] }}</p>
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Sedang Ditangani</span>
                    <p class="text-3xl sm:text-4xl font-black text-amber-600 mt-1 leading-none">{{ $stats['active'] }}</p>
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Anggota Online</span>
                    <p class="text-3xl sm:text-4xl font-black text-emerald-600 mt-1 leading-none">{{ $stats['members_online'] }}</p>
                </div>
            </div>
            <a href="{{ route('admin.reports.index', ['status' => 'completed']) }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm flex flex-col justify-between transition-colors hover:border-slate-300 hover:bg-slate-50">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Selesai Hari Ini</span>
                    <p class="text-3xl sm:text-4xl font-black text-slate-700 mt-1 leading-none">{{ $stats['completed_today'] }}</p>
                </div>
            </a>
        </div>

        {{-- ── MAIN PANEL: MAP & LISTS ── --}}
        <div class="grid gap-4 lg:grid-cols-[1fr_380px]">
            
            {{-- Peta Operasional --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm flex flex-col">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-800">Peta Taktis Operasional</h2>
                        <p id="mapMeta" class="text-xs text-slate-500 mt-0.5">Memuat data peta...</p>
                    </div>
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                </div>
                <div id="adminMap" class="h-[500px] lg:h-[650px] z-10"></div>
            </div>

            {{-- Sidebar --}}
            <aside class="space-y-4 flex flex-col justify-between h-full">
                
                {{-- Laporan Aktif --}}
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm flex-1">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Daftar Laporan Aktif</h2>
                            <p class="text-xs text-slate-500">Kasus yang butuh penanganan</p>
                        </div>
                        <span id="reportsCount" class="rounded bg-red-100 px-2 py-0.5 text-xs font-extrabold text-red-700">{{ $reports->count() }}</span>
                    </div>
                    <div id="activeReportsList" class="mt-3 space-y-2.5 max-h-[220px] lg:max-h-[240px] overflow-y-auto pr-1">
                        @forelse($reports as $report)
                            @php
                                $priorityClass = match ($report->priority) {
                                    'critical' => 'bg-red-50 text-red-700 border-red-100',
                                    'high' => 'bg-orange-50 text-orange-700 border-orange-100',
                                    'medium' => 'bg-amber-50 text-amber-700 border-amber-100',
                                    default => 'bg-slate-50 text-slate-600 border-slate-100',
                                };
                                $priorityBorder = match ($report->priority) {
                                    'critical' => 'border-l-red-500',
                                    'high' => 'border-l-orange-500',
                                    'medium' => 'border-l-amber-500',
                                    default => 'border-l-slate-400',
                                };
                            @endphp
                            <a href="{{ route('admin.reports.show', $report) }}" class="block rounded border border-slate-200 border-l-4 {{ $priorityBorder }} p-3.5 bg-white hover:bg-slate-50 transition-colors shadow-sm">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="font-bold text-slate-900 text-sm sm:text-base leading-tight">{{ $report->incident_type }}</p>
                                        <p class="text-xs text-slate-500 mt-1 font-mono">{{ $report->tracking_code }} • {{ $report->created_at->diffForHumans() }}</p>
                                    </div>
                                    <span class="rounded px-2 py-0.5 text-xs font-extrabold uppercase tracking-wide {{ $priorityClass }}">{{ $report->priority }}</span>
                                </div>
                                <div class="mt-3 flex items-center justify-between text-xs border-t border-slate-100 pt-2 text-slate-600">
                                    <div>Status: <span class="font-bold text-slate-800">{{ \App\Http\Controllers\PublicTrackingController::statusLabel($report->status) }}</span></div>
                                    <div>Petugas: <span class="font-bold text-slate-800">{{ $report->assignedMember?->name ?? 'Belum' }}</span></div>
                                </div>
                            </a>
                        @empty
                            <p class="rounded bg-slate-50 p-4 text-xs text-slate-500 text-center">Belum ada laporan aktif.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Anggota Lapangan --}}
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm flex-1">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-sm font-bold text-slate-900">Anggota TIMSAR</h2>
                        <p class="text-xs text-slate-500">Status pelacakan lapangan</p>
                    </div>
                    <div class="mt-3 space-y-2.5 max-h-[300px] lg:max-h-[350px] overflow-y-auto pr-1">
                        @foreach($members as $member)
                            @php
                                $isOnline = $member->memberLocation?->last_seen_at?->gt(now()->subSeconds(90));
                            @endphp
                            <div class="rounded border border-slate-200 p-3.5 bg-white hover:bg-slate-50 transition-colors shadow-sm">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="font-bold text-slate-900 text-sm sm:text-base leading-tight">{{ $member->name }}</p>
                                    <span class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-extrabold uppercase tracking-wide {{ $isOnline ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $isOnline ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}"></span>
                                        {{ $isOnline ? 'Online' : 'Offline' }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500 leading-none">{{ $member->phone }} • <span class="font-mono text-slate-450">{{ $member->memberLocation?->network_type ?? 'offline' }}</span></p>
                                <p class="mt-2 text-xs text-slate-400">
                                    Aktif: {{ $member->memberLocation?->last_seen_at?->diffForHumans() ?? '-' }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>

            </aside>
        </div>
    </section>

    @push('scripts')
        <style>
            /* Custom Leaflet styling to look modern */
            .leaflet-popup-content-wrapper {
                border-radius: 0.5rem !important;
                box-shadow: 0 4px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05) !important;
                border: 1px solid #e2e8f0 !important;
                padding: 0.25rem !important;
            }
            .leaflet-popup-content {
                font-family: inherit !important;
                font-size: 0.825rem !important;
                color: #334155 !important;
                line-height: 1.45 !important;
                margin: 0.5rem 0.75rem !important;
            }
            .leaflet-popup-tip {
                background: white !important;
                box-shadow: none !important;
            }
        </style>
        <script>
            const map = L.map('adminMap').setView([-8.586, 116.1], 12);
            TimsarMap.addTiles(map);
            let markers = [];
            let latestReportId = {{ $latestReportId }};
            const alertAudio = new Audio(@json(asset('audio/alarm-darurat.mp3')));
            alertAudio.loop = true;
            alertAudio.preload = 'auto';
            let activeAdminAlertReportId = null;
            let alertVibrationInterval = null;
            const notificationButton = document.getElementById('adminNotificationButton');
            const stopAlarmButton = document.getElementById('stopAdminAlarmButton');
            const activeReportsList = document.getElementById('activeReportsList');
            const reportsCount = document.getElementById('reportsCount');

            function clearMarkers() {
                markers.forEach((marker) => marker.remove());
                markers = [];
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function priorityClass(priority) {
                if (priority === 'critical') return 'bg-red-100 text-red-700';
                if (priority === 'high') return 'bg-orange-100 text-orange-700';
                if (priority === 'medium') return 'bg-amber-100 text-amber-700';
                return 'bg-slate-100 text-slate-650';
            }

            function formatReportTime(value) {
                if (!value) return '-';
                return new Date(value).toLocaleString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    hour: '2-digit',
                    minute: '2-digit',
                });
            }

            function renderReports(reports) {
                reportsCount.textContent = reports.length;

                if (!reports.length) {
                    activeReportsList.innerHTML = '<p class="rounded bg-slate-50 p-4 text-xs text-slate-500 text-center">Belum ada laporan aktif.</p>';
                    return;
                }

                activeReportsList.innerHTML = reports.slice(0, 20).map((report) => {
                    const priorityBorder = {
                        critical: 'border-l-red-500',
                        high: 'border-l-orange-500',
                        medium: 'border-l-amber-500',
                        low: 'border-l-slate-400',
                    }[report.priority] || 'border-l-slate-300';

                    const priorityLabelClass = {
                        critical: 'bg-red-50 text-red-700 border-red-100',
                        high: 'bg-orange-50 text-orange-700 border-orange-100',
                        medium: 'bg-amber-50 text-amber-700 border-amber-100',
                    }[report.priority] || 'bg-slate-50 text-slate-650 border-slate-100';

                    return `
                        <a href="${report.url}" data-report-alarm-stop class="block rounded border border-slate-200 border-l-4 ${priorityBorder} p-3.5 bg-white hover:bg-slate-50 transition-colors shadow-sm">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-bold text-slate-900 text-sm sm:text-base leading-tight">${escapeHtml(report.incident_type)}</p>
                                    <p class="text-xs text-slate-500 mt-1 font-mono">${escapeHtml(report.tracking_code)} - ${formatReportTime(report.created_at)}</p>
                                </div>
                                <span class="rounded px-2 py-0.5 text-xs font-extrabold uppercase tracking-wide ${priorityLabelClass}">${escapeHtml(report.priority).toUpperCase()}</span>
                            </div>
                            <div class="mt-3 flex items-center justify-between text-xs border-t border-slate-100 pt-2 text-slate-600">
                                <div>Status: <span class="font-bold text-slate-800">${escapeHtml(report.status_label)}</span></div>
                                <div>Petugas: <span class="font-bold text-slate-800">${escapeHtml(report.assigned_member || 'Belum')}</span></div>
                            </div>
                        </a>
                    `;
                }).join('');
            }

            function updateNotificationUi() {
                if (!('Notification' in window)) {
                    notificationButton.disabled = true;
                    notificationButton.textContent = 'Notifikasi tidak didukung';
                    return;
                }

                if (Notification.permission === 'granted') {
                    notificationButton.disabled = false;
                    notificationButton.textContent = 'Notifikasi aktif';
                    notificationButton.className = 'rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-4 py-2.5 text-xs font-bold text-emerald-650 hover:bg-emerald-500/20 transition-colors';
                    return;
                }

                if (Notification.permission === 'denied') {
                    notificationButton.disabled = true;
                    notificationButton.textContent = 'Notifikasi diblokir';
                    notificationButton.className = 'rounded-lg border border-red-500/20 bg-red-500/10 px-4 py-2.5 text-xs font-bold text-red-500 cursor-not-allowed';
                    return;
                }

                notificationButton.disabled = false;
                notificationButton.textContent = 'Aktifkan notifikasi';
                notificationButton.className = 'rounded-lg border border-slate-300 bg-white hover:bg-slate-50 px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition-colors';
            }

            function unlockAlertAudio() {
                alertAudio.muted = true;
                alertAudio.play()
                    .then(() => {
                        alertAudio.pause();
                        alertAudio.currentTime = 0;
                        alertAudio.muted = false;
                    })
                    .catch(() => {
                        alertAudio.muted = false;
                    });
            }

            function stopAlertAlarm() {
                alertAudio.pause();
                alertAudio.currentTime = 0;
                activeAdminAlertReportId = null;
                if (alertVibrationInterval) {
                    window.clearInterval(alertVibrationInterval);
                    alertVibrationInterval = null;
                }
                if ('vibrate' in navigator) {
                    navigator.vibrate(0);
                }
                document.title = 'Dashboard Admin TIMSAR';
                stopAlarmButton.classList.add('hidden');
            }

            function startAlertAlarm(report) {
                if (activeAdminAlertReportId === report.id) return;
                stopAlertAlarm();
                activeAdminAlertReportId = report.id;
                alertAudio.muted = false;
                alertAudio.currentTime = 0;
                alertAudio.play().catch(() => {
                    notificationButton.textContent = 'Klik untuk aktifkan suara';
                });
                if ('vibrate' in navigator) {
                    navigator.vibrate([700, 200, 700, 200, 1000]);
                    alertVibrationInterval = window.setInterval(() => {
                        navigator.vibrate([700, 200, 700, 200, 1000]);
                    }, 3200);
                }
                stopAlarmButton.classList.remove('hidden');
            }

            notificationButton.addEventListener('click', async () => {
                unlockAlertAudio();
                if ('Notification' in window) {
                    await Notification.requestPermission();
                }
                updateNotificationUi();
            });

            stopAlarmButton.addEventListener('click', stopAlertAlarm);

            function notifyNewReport(report) {
                document.title = 'Laporan baru - TIMSAR';
                startAlertAlarm(report);

                if ('Notification' in window && Notification.permission === 'granted') {
                    const notification = new Notification('Laporan darurat baru', {
                        body: `${report.incident_type} - ${report.tracking_code}`,
                        tag: `report-${report.id}`,
                        requireInteraction: true,
                    });

                    notification.onclick = () => {
                        stopAlertAlarm();
                        window.focus();
                        window.location.href = report.url;
                    };
                }
            }

            document.addEventListener('click', (event) => {
                const link = event.target.closest('a[data-report-alarm-stop], a[href*="/admin/reports/"]');
                if (!link) return;
                stopAlertAlarm();
            });

            async function refreshMap() {
                const res = await fetch('{{ route('admin.map-data') }}');
                if (!res.ok) return;

                const data = await res.json();
                clearMarkers();
                renderReports(data.reports);
                if (
                    activeAdminAlertReportId &&
                    !data.reports.some((report) => report.id === activeAdminAlertReportId)
                ) {
                    stopAlertAlarm();
                }

                const newestReport = data.reports
                    .filter((report) => report.id > latestReportId)
                    .sort((a, b) => b.id - a.id)[0];

                if (newestReport) {
                    notifyNewReport(newestReport);
                }

                latestReportId = Math.max(latestReportId, data.latest_report_id || 0);

                data.reports.forEach((report) => {
                    const marker = L.marker([report.latitude, report.longitude], {
                        icon: TimsarMap.icon('incident', { pulse: report.priority === 'critical' }),
                    }).addTo(map)
                        .bindPopup(`
                            <div class="space-y-1">
                                <div class="font-bold text-slate-900 text-sm">${escapeHtml(report.incident_type)}</div>
                                <div class="text-xs uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-red-50 text-red-700 inline-block font-mono">${escapeHtml(report.status_label)}</div>
                                <div class="text-xs text-slate-500 mt-1">Petugas: <span class="font-semibold text-slate-700">${escapeHtml(report.assigned_member || 'Belum ditugaskan')}</span></div>
                                <div class="pt-1.5 border-t border-slate-100 mt-1.5">
                                    <a href="${report.url}" data-report-alarm-stop class="text-xs font-bold text-red-600 hover:text-red-700 inline-flex items-center gap-0.5">Lihat Detail Laporan &rarr;</a>
                                </div>
                            </div>
                        `);
                    markers.push(marker);
                });

                data.members.forEach((member) => {
                    const marker = L.marker([member.latitude, member.longitude], {
                        icon: TimsarMap.icon('member', { pulse: member.is_online, offline: !member.is_online }),
                    }).addTo(map).bindPopup(`
                        <div class="space-y-1">
                            <div class="font-bold text-slate-900 text-sm">${member.name}</div>
                            <div class="text-xs uppercase font-bold tracking-wider px-1.5 py-0.5 rounded inline-block ${member.is_online ? 'bg-emerald-50 text-emerald-700 font-mono' : 'bg-slate-50 text-slate-500 font-mono'}">
                                ${member.is_online ? 'Online' : 'Offline'}
                            </div>
                            <div class="text-xs text-slate-500 mt-1">Jaringan: <span class="font-semibold text-slate-700">${member.network_type}</span></div>
                        </div>
                    `);
                    markers.push(marker);
                });

                document.getElementById('mapMeta').textContent = `${data.reports.length} aktif, ${data.members.length} petugas. Update ${new Date().toLocaleTimeString('id-ID')}`;
            }

            updateNotificationUi();
            refreshMap();
            setInterval(refreshMap, 3000);
        </script>
    @endpush
</x-layouts.app>
