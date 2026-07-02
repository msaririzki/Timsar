<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekap Bukti Operasi {{ $report->tracking_code }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .print-break { break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 antialiased">
    @php
        $summary = $evidence['summary'];
        $logs = $evidence['logs'];
        $timeline = $evidence['timeline'];
        $cellPoints = collect($evidence['trail']['cell_points'] ?? []);
        $handovers = collect($evidence['trail']['handovers'] ?? []);
        $distance = $summary['distance_meters'] >= 1000
            ? number_format($summary['distance_meters'] / 1000, 2) . ' km'
            : number_format($summary['distance_meters']) . ' m';
    @endphp

    <main class="mx-auto max-w-5xl bg-white px-6 py-6 shadow-sm sm:my-6 sm:px-8">
        <div class="no-print mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
            <a href="{{ route('admin.reports.show', $report) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700">Kembali</a>
            <button type="button" onclick="window.print()" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white">Cetak / Simpan PDF</button>
        </div>

        <header class="border-b-4 border-slate-900 pb-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-red-600">TIMSAR NTB</p>
                    <h1 class="mt-2 text-3xl font-black text-slate-950">Rekap Bukti Operasi</h1>
                    <p class="mt-1 text-sm font-semibold text-slate-600">{{ $report->incident_type }} - {{ $report->tracking_code }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-3 text-right text-xs text-slate-600">
                    <p class="font-bold text-slate-900">Dibuat</p>
                    <p>{{ $generatedAt->format('d M Y H:i:s') }}</p>
                    <p class="mt-2 font-bold text-slate-900">Status</p>
                    <p>{{ $summary['report_status'] }}</p>
                </div>
            </div>
        </header>

        <section class="print-break mt-5 grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl border border-slate-200 p-4">
                <h2 class="text-sm font-black uppercase tracking-wide text-slate-500">Data laporan</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div><dt class="font-bold text-slate-500">Pelapor</dt><dd class="font-black">{{ $report->reporter_name }} - {{ $report->reporter_phone }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Lokasi laporan</dt><dd class="font-mono">{{ number_format($report->latitude, 7) }}, {{ number_format($report->longitude, 7) }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Akurasi pelapor</dt><dd>{{ $report->accuracy ? number_format($report->accuracy) . ' m' : '-' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Deskripsi</dt><dd>{{ $report->description }}</dd></div>
                </dl>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
                <h2 class="text-sm font-black uppercase tracking-wide text-slate-500">Data petugas</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div><dt class="font-bold text-slate-500">Petugas</dt><dd class="font-black">{{ $summary['member_name'] }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Status tugas</dt><dd>{{ $summary['assignment_status'] }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Mulai rekam</dt><dd>{{ $summary['started_at']?->format('d M Y H:i:s') ?? '-' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Update terakhir</dt><dd>{{ $summary['last_at']?->format('d M Y H:i:s') ?? '-' }}</dd></div>
                </dl>
            </div>
        </section>

        <section class="print-break mt-5 rounded-xl border border-slate-200 p-4">
            <h2 class="text-sm font-black uppercase tracking-wide text-slate-500">Ringkasan mobile computing</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-5">
                <div class="rounded-lg bg-blue-50 p-3"><p class="text-xs font-bold text-blue-600">Titik GPS</p><p class="text-2xl font-black text-blue-950">{{ number_format($summary['gps_points']) }}</p></div>
                <div class="rounded-lg bg-amber-50 p-3"><p class="text-xs font-bold text-amber-700">Log BTS</p><p class="text-2xl font-black text-amber-950">{{ number_format($summary['cell_observations']) }}</p></div>
                <div class="rounded-lg bg-emerald-50 p-3"><p class="text-xs font-bold text-emerald-700">Pindah jaringan</p><p class="text-2xl font-black text-emerald-950">{{ number_format($summary['network_changes']) }}x</p></div>
                <div class="rounded-lg bg-orange-50 p-3"><p class="text-xs font-bold text-orange-700">Handover BTS</p><p class="text-2xl font-black text-orange-950">{{ number_format($summary['handovers']) }}x</p></div>
                <div class="rounded-lg bg-slate-100 p-3"><p class="text-xs font-bold text-slate-600">Jalur terekam</p><p class="text-2xl font-black text-slate-950">{{ $distance }}</p></div>
            </div>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">BTS awal</p>
                    <p class="mt-1 text-sm font-black">
                        @if($summary['first_cell'])
                            {{ $summary['first_cell']['operator'] }} {{ $summary['first_cell']['radio_type'] }} / Cell {{ $summary['first_cell']['cell_id'] }}
                        @else
                            Belum tersedia
                        @endif
                    </p>
                </div>
                <div class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">BTS terbaru</p>
                    <p class="mt-1 text-sm font-black">
                        @if($summary['latest_cell'])
                            {{ $summary['latest_cell']['operator'] }} {{ $summary['latest_cell']['radio_type'] }} / Cell {{ $summary['latest_cell']['cell_id'] }}
                        @else
                            Belum tersedia
                        @endif
                    </p>
                </div>
            </div>
        </section>

        <section class="print-break mt-5 rounded-xl border border-slate-200 p-4">
            <h2 class="text-sm font-black uppercase tracking-wide text-slate-500">Titik BTS di peta</h2>
            <div class="mt-3 space-y-2">
                @forelse($cellPoints as $point)
                    <div class="rounded-lg border border-amber-100 bg-amber-50/60 p-3 text-sm">
                        <p class="font-black text-amber-950">{{ $point['event'] === 'first' ? 'BTS awal' : 'BTS berubah' }} - {{ $point['cell']['operator'] }} {{ $point['cell']['radio_type'] }} / Cell {{ $point['cell']['cell_id'] }}</p>
                        <p class="mt-1 font-mono text-xs text-slate-600">{{ number_format($point['latitude'], 7) }}, {{ number_format($point['longitude'], 7) }} - {{ \Illuminate\Support\Carbon::parse($point['observed_at'])->format('d M Y H:i:s') }}</p>
                    </div>
                @empty
                    <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">Belum ada titik BTS.</p>
                @endforelse
            </div>
        </section>

        <section class="print-break mt-5 rounded-xl border border-slate-200 p-4">
            <h2 class="text-sm font-black uppercase tracking-wide text-slate-500">Timeline operasi</h2>
            <div class="mt-3 space-y-2">
                @foreach($timeline as $item)
                    <div class="grid gap-2 rounded-lg bg-slate-50 p-3 text-sm sm:grid-cols-[160px_1fr]">
                        <p class="font-mono text-xs text-slate-500">{{ $item['time']->format('d M Y H:i:s') }}</p>
                        <div>
                            <p class="font-black text-slate-900">{{ $item['event'] }} <span class="font-semibold text-slate-500">- {{ $item['actor'] }}</span></p>
                            @if($item['note'])<p class="mt-1 text-xs text-slate-600">{{ $item['note'] }}</p>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="mt-5 rounded-xl border border-slate-200 p-4">
            <h2 class="text-sm font-black uppercase tracking-wide text-slate-500">Log mobile computing</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2">Waktu</th>
                            <th class="px-3 py-2">GPS</th>
                            <th class="px-3 py-2">Jaringan</th>
                            <th class="px-3 py-2">BTS / Cell</th>
                            <th class="px-3 py-2">Sinyal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($logs as $log)
                            <tr class="align-top">
                                <td class="whitespace-nowrap px-3 py-2 font-mono">{{ $log['recorded_at']?->format('d M Y H:i:s') }}</td>
                                <td class="px-3 py-2 font-mono">{{ number_format($log['latitude'], 6) }}, {{ number_format($log['longitude'], 6) }}<br><span class="font-sans text-slate-500">Akurasi {{ $log['accuracy'] !== null ? number_format($log['accuracy']) . ' m' : '-' }}</span></td>
                                <td class="px-3 py-2 font-bold">{{ strtoupper($log['network_type']) }}</td>
                                <td class="px-3 py-2">
                                    @if($log['cell'])
                                        <span class="font-black">{{ $log['cell']['operator'] }} {{ $log['cell']['radio_type'] }}</span><br>
                                        <span class="font-mono">Cell {{ $log['cell']['cell_id'] }}</span><br>
                                        TAC/LAC {{ $log['cell']['tac_or_lac'] ?? '-' }} - PCI {{ $log['cell']['pci_or_psc'] ?? '-' }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-3 py-2 font-mono">
                                    @if($log['signal'])
                                        RSRP {{ $log['signal']['rsrp_dbm'] ?? '-' }} dBm<br>
                                        RSRQ {{ $log['signal']['rsrq_db'] ?? '-' }} / SINR {{ $log['signal']['sinr_db'] ?? '-' }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-8 text-center text-slate-500">Belum ada log.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
