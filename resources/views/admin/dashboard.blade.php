<x-layouts.app title="Dashboard Admin TIMSAR">
    <section class="space-y-5">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <p class="text-sm font-black uppercase text-red-600">Posko TIMSAR</p>
                <h1 class="mt-1 text-3xl font-black md:text-4xl">Dashboard laporan dan anggota aktif</h1>
                <p class="mt-2 text-slate-600">Data peta, laporan, dan anggota diperbarui otomatis tiap 3 detik dari server.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <button id="adminNotificationButton" type="button" class="rounded-xl border border-red-200 bg-white px-4 py-3 text-center font-black text-red-700">
                    Aktifkan notifikasi
                </button>
                <a href="{{ route('public.report') }}" class="rounded-xl bg-red-600 px-4 py-3 text-center font-black text-white">Buka Form Lapor</a>
            </div>
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
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-black">Laporan aktif</h2>
                            <p class="text-sm text-slate-500">Antrean kasus yang masih perlu dipantau posko.</p>
                        </div>
                        <span id="reportsCount" class="rounded-full bg-red-100 px-3 py-1 text-xs font-black text-red-700">{{ $reports->count() }}</span>
                    </div>
                    <div id="activeReportsList" class="mt-4 space-y-3">
                        @forelse($reports as $report)
                            @php
                                $priorityClass = match ($report->priority) {
                                    'critical' => 'bg-red-100 text-red-700',
                                    'high' => 'bg-orange-100 text-orange-700',
                                    'medium' => 'bg-amber-100 text-amber-700',
                                    default => 'bg-slate-100 text-slate-600',
                                };
                            @endphp
                            <a href="{{ route('admin.reports.show', $report) }}" class="block rounded-xl border border-slate-200 p-4 hover:border-red-300 hover:bg-red-50">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-black">{{ $report->incident_type }}</p>
                                        <p class="text-sm text-slate-500">{{ $report->tracking_code }} - {{ $report->created_at->diffForHumans() }}</p>
                                    </div>
                                    <span class="rounded-full {{ $priorityClass }} px-3 py-1 text-xs font-black">{{ strtoupper($report->priority) }}</span>
                                </div>
                                <div class="mt-3 grid gap-2 text-sm text-slate-600">
                                    <p>Status: <span class="font-black text-slate-900">{{ \App\Http\Controllers\PublicTrackingController::statusLabel($report->status) }}</span></p>
                                    <p>Petugas: <span class="font-black text-slate-900">{{ $report->assignedMember?->name ?? 'Belum ditugaskan' }}</span></p>
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
                                    <span class="rounded-full {{ $member->memberLocation?->last_seen_at?->gt(now()->subSeconds(90)) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }} px-3 py-1 text-xs font-black">
                                        {{ $member->memberLocation?->last_seen_at?->gt(now()->subSeconds(90)) ? 'Online' : 'Offline' }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-slate-500">{{ $member->phone }} - {{ $member->memberLocation?->network_type ?? 'unknown' }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-400">
                                    Terakhir aktif: {{ $member->memberLocation?->last_seen_at?->diffForHumans() ?? '-' }}
                                </p>
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
            let latestReportId = {{ $latestReportId }};
            let alertAudioContext = null;
            const notificationButton = document.getElementById('adminNotificationButton');
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
                return 'bg-slate-100 text-slate-600';
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
                    activeReportsList.innerHTML = '<p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Belum ada laporan.</p>';
                    return;
                }

                activeReportsList.innerHTML = reports.slice(0, 20).map((report) => `
                    <a href="${report.url}" class="block rounded-xl border border-slate-200 p-4 hover:border-red-300 hover:bg-red-50">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-black">${escapeHtml(report.incident_type)}</p>
                                <p class="text-sm text-slate-500">${escapeHtml(report.tracking_code)} - ${formatReportTime(report.created_at)}</p>
                            </div>
                            <span class="rounded-full ${priorityClass(report.priority)} px-3 py-1 text-xs font-black">${escapeHtml(report.priority).toUpperCase()}</span>
                        </div>
                        <div class="mt-3 grid gap-2 text-sm text-slate-600">
                            <p>Status: <span class="font-black text-slate-900">${escapeHtml(report.status_label)}</span></p>
                            <p>Petugas: <span class="font-black text-slate-900">${escapeHtml(report.assigned_member || 'Belum ditugaskan')}</span></p>
                        </div>
                    </a>
                `).join('');
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
                    notificationButton.className = 'rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-center font-black text-emerald-700';
                    return;
                }

                if (Notification.permission === 'denied') {
                    notificationButton.disabled = true;
                    notificationButton.textContent = 'Notifikasi diblokir';
                    return;
                }

                notificationButton.disabled = false;
                notificationButton.textContent = 'Aktifkan notifikasi';
            }

            function unlockAlertAudio() {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;

                alertAudioContext ??= new AudioContext();
                if (alertAudioContext.state === 'suspended') {
                    alertAudioContext.resume();
                }
            }

            function playAlertTone() {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;

                const context = alertAudioContext || new AudioContext();
                if (context.state === 'suspended') {
                    context.resume();
                }

                const oscillator = context.createOscillator();
                const gain = context.createGain();
                oscillator.type = 'square';
                oscillator.frequency.setValueAtTime(760, context.currentTime);
                oscillator.frequency.setValueAtTime(980, context.currentTime + 0.16);
                gain.gain.setValueAtTime(0.001, context.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.2, context.currentTime + 0.03);
                gain.gain.exponentialRampToValueAtTime(0.001, context.currentTime + 0.55);
                oscillator.connect(gain);
                gain.connect(context.destination);
                oscillator.start();
                oscillator.stop(context.currentTime + 0.6);
            }

            notificationButton.addEventListener('click', async () => {
                unlockAlertAudio();
                if ('Notification' in window) {
                    await Notification.requestPermission();
                }
                updateNotificationUi();
            });

            function notifyNewReport(report) {
                document.title = 'Laporan baru - TIMSAR';
                if ('vibrate' in navigator) {
                    navigator.vibrate([300, 120, 300]);
                }
                playAlertTone();

                if ('Notification' in window && Notification.permission === 'granted') {
                    const notification = new Notification('Laporan darurat baru', {
                        body: `${report.incident_type} - ${report.tracking_code}`,
                        tag: `report-${report.id}`,
                        requireInteraction: true,
                    });

                    notification.onclick = () => {
                        window.focus();
                        window.location.href = report.url;
                    };
                }
            }

            async function refreshMap() {
                const res = await fetch('{{ route('admin.map-data') }}');
                if (!res.ok) return;

                const data = await res.json();
                clearMarkers();
                renderReports(data.reports);

                const newestReport = data.reports
                    .filter((report) => report.id > latestReportId)
                    .sort((a, b) => b.id - a.id)[0];

                if (newestReport) {
                    notifyNewReport(newestReport);
                }

                latestReportId = Math.max(latestReportId, data.latest_report_id || 0);

                data.reports.forEach((report) => {
                    const marker = L.marker([report.latitude, report.longitude]).addTo(map)
                        .bindPopup(`<strong>${escapeHtml(report.incident_type)}</strong><br>${escapeHtml(report.status_label)}<br>${escapeHtml(report.assigned_member || 'Belum ditugaskan')}<br><a href="${report.url}">Buka detail</a>`);
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

            updateNotificationUi();
            refreshMap();
            setInterval(refreshMap, 3000);
        </script>
    @endpush
</x-layouts.app>
