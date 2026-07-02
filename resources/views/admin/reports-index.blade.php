<x-layouts.app title="Riwayat Laporan TIMSAR">
    <section class="space-y-5">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-bold uppercase text-slate-500">Arsip Operasi</p>
                <h1 class="mt-1 text-2xl font-black text-slate-950 sm:text-3xl">Riwayat Laporan</h1>
                <p class="mt-1 text-sm text-slate-500">Laporan yang telah selesai ditangani atau dibatalkan oleh posko.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white">Kembali ke Dashboard</a>
        </header>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-bold uppercase text-slate-500">Total Arsip</p>
                <p class="mt-1 text-3xl font-black text-slate-900">{{ $stats['total'] }}</p>
            </div>
            <div class="rounded-lg border border-emerald-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-bold uppercase text-emerald-700">Selesai</p>
                <p class="mt-1 text-3xl font-black text-emerald-700">{{ $stats['completed'] }}</p>
            </div>
            <div class="rounded-lg border border-slate-300 bg-white p-4 shadow-sm">
                <p class="text-xs font-bold uppercase text-slate-600">Dibatalkan</p>
                <p class="mt-1 text-3xl font-black text-slate-700">{{ $stats['cancelled'] }}</p>
            </div>
            <div class="rounded-lg border border-blue-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-bold uppercase text-blue-700">Bulan Ini</p>
                <p class="mt-1 text-3xl font-black text-blue-700">{{ $stats['this_month'] }}</p>
            </div>
        </div>

        <div class="border-y border-slate-200 bg-white py-4">
            <div class="flex flex-wrap gap-2" aria-label="Filter status laporan">
                @foreach(['all' => 'Semua', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'] as $value => $label)
                    <a href="{{ route('admin.reports.index', array_filter(['status' => $value, 'q' => $filters['q'], 'date_from' => $filters['date_from'], 'date_to' => $filters['date_to']])) }}" class="rounded-lg px-4 py-2 text-sm font-bold {{ $filters['status'] === $value ? 'bg-slate-900 text-white' : 'border border-slate-300 bg-white text-slate-600 hover:bg-slate-50' }}">{{ $label }}</a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('admin.reports.index') }}" class="mt-4 grid gap-3 md:grid-cols-[minmax(220px,1fr)_180px_180px_auto]">
                <input type="hidden" name="status" value="{{ $filters['status'] }}">
                <div>
                    <label for="q" class="mb-1 block text-xs font-bold text-slate-600">Cari laporan</label>
                    <input id="q" name="q" value="{{ $filters['q'] }}" type="search" class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-100" placeholder="Kode, nama, HP, atau kejadian">
                </div>
                <div>
                    <label for="date_from" class="mb-1 block text-xs font-bold text-slate-600">Dari tanggal</label>
                    <input id="date_from" name="date_from" value="{{ $filters['date_from'] }}" type="date" class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-100">
                </div>
                <div>
                    <label for="date_to" class="mb-1 block text-xs font-bold text-slate-600">Sampai tanggal</label>
                    <input id="date_to" name="date_to" value="{{ $filters['date_to'] }}" type="date" class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-100">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="h-10 rounded-lg bg-slate-900 px-4 text-sm font-bold text-white">Terapkan</button>
                    <a href="{{ route('admin.reports.index') }}" class="grid h-10 place-items-center rounded-lg border border-slate-300 px-3 text-sm font-bold text-slate-600">Reset</a>
                </div>
            </form>
        </div>

        <div class="overflow-hidden border-y border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                <div>
                    <h2 class="font-bold text-slate-900">Hasil Pencarian</h2>
                    <p class="text-xs text-slate-500">{{ $reports->total() }} laporan ditemukan</p>
                </div>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Laporan</th>
                            <th class="px-4 py-3">Pelapor</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Petugas</th>
                            <th class="px-4 py-3">Ditutup</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($reports as $report)
                            @php($closedAt = $report->closed_at ?? $report->activeAssignment?->completed_at ?? $report->updated_at)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <p class="font-bold text-slate-900">{{ $report->incident_type }}</p>
                                    <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $report->tracking_code }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-800">{{ $report->reporter_name }}</p>
                                    <p class="text-xs text-slate-500">{{ $report->reporter_phone }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $report->status === \App\Models\Report::STATUS_COMPLETED ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                        {{ \App\Http\Controllers\PublicTrackingController::statusLabel($report->status) }}
                                    </span>
                                    @if($report->closure_notes)<p class="mt-1 max-w-56 truncate text-xs text-slate-500" title="{{ $report->closure_notes }}">{{ $report->closure_notes }}</p>@endif
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $report->activeAssignment?->member?->name ?? $report->assignedMember?->name ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-700">{{ $closedAt->format('d M Y') }}</p>
                                    <p class="text-xs text-slate-500">{{ $closedAt->format('H:i') }} WITA</p>
                                </td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('admin.reports.show', $report) }}" class="font-bold text-red-600 hover:text-red-700">Lihat detail</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">Tidak ada laporan yang sesuai dengan filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 md:hidden">
                @forelse($reports as $report)
                    @php($closedAt = $report->closed_at ?? $report->activeAssignment?->completed_at ?? $report->updated_at)
                    <a href="{{ route('admin.reports.show', $report) }}" class="block p-4 hover:bg-slate-50">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-bold text-slate-900">{{ $report->incident_type }}</p>
                                <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $report->tracking_code }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2 py-1 text-xs font-bold {{ $report->status === \App\Models\Report::STATUS_COMPLETED ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">{{ \App\Http\Controllers\PublicTrackingController::statusLabel($report->status) }}</span>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-500">
                            <p><span class="block font-bold text-slate-700">Pelapor</span>{{ $report->reporter_name }}</p>
                            <p><span class="block font-bold text-slate-700">Ditutup</span>{{ $closedAt->format('d M Y, H:i') }}</p>
                        </div>
                        @if($report->closure_notes)<p class="mt-3 line-clamp-2 text-xs text-slate-600">{{ $report->closure_notes }}</p>@endif
                    </a>
                @empty
                    <p class="px-4 py-12 text-center text-sm text-slate-500">Tidak ada laporan yang sesuai dengan filter.</p>
                @endforelse
            </div>
        </div>

        @if($reports->hasPages())
            <div>{{ $reports->links() }}</div>
        @endif
    </section>
</x-layouts.app>
