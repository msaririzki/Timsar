<x-layouts.app title="Detail Tugas TIMSAR">
    <section class="mx-auto max-w-4xl space-y-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-black uppercase text-red-600">{{ $assignment->report->tracking_code }}</p>
            <h1 class="mt-1 text-3xl font-black">{{ $assignment->report->incident_type }}</h1>
            <p class="mt-2 text-slate-600">{{ $assignment->report->description }}</p>
            <div class="mt-5 grid gap-3 md:grid-cols-3">
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase text-slate-500">Status tugas</p>
                    <p class="font-black">{{ \App\Http\Controllers\PublicTrackingController::assignmentLabel($assignment->status) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase text-slate-500">Jarak</p>
                    <p class="font-black">{{ $assignment->distance_meters ? number_format($assignment->distance_meters / 1000, 2) . ' km' : '-' }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase text-slate-500">Estimasi</p>
                    <p class="font-black">{{ $assignment->duration_seconds ? round($assignment->duration_seconds / 60) . ' menit' : '-' }}</p>
                </div>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            @if($assignment->status === 'assigned')
                <form method="POST" action="{{ route('member.assignments.accept', $assignment) }}">@csrf<button class="w-full rounded-xl bg-slate-900 px-4 py-3 font-black text-white">Terima</button></form>
            @endif
            @if(in_array($assignment->status, ['assigned', 'accepted']))
                <form method="POST" action="{{ route('member.assignments.start', $assignment) }}">@csrf<button class="w-full rounded-xl bg-blue-600 px-4 py-3 font-black text-white">Mulai Jalan</button></form>
            @endif
            @if($assignment->status === 'on_the_way')
                <form method="POST" action="{{ route('member.assignments.arrive', $assignment) }}">@csrf<button class="w-full rounded-xl bg-amber-500 px-4 py-3 font-black text-white">Sampai</button></form>
            @endif
            @if(in_array($assignment->status, ['arrived', 'on_the_way']))
                <form method="POST" action="{{ route('member.assignments.handling', $assignment) }}">@csrf<button class="w-full rounded-xl bg-purple-600 px-4 py-3 font-black text-white">Tangani</button></form>
            @endif
            @if(in_array($assignment->status, ['handling', 'arrived']))
                <form method="POST" action="{{ route('member.assignments.complete', $assignment) }}">@csrf<button class="w-full rounded-xl bg-emerald-600 px-4 py-3 font-black text-white">Selesai</button></form>
            @endif
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div id="assignmentMap" class="h-[540px]"></div>
        </div>
    </section>

    @push('scripts')
        <script>
            const reportPoint = [{{ $assignment->report->latitude }}, {{ $assignment->report->longitude }}];
            const map = L.map('assignmentMap').setView(reportPoint, 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
            L.marker(reportPoint).addTo(map).bindPopup('Lokasi kejadian');
            @if($assignment->member?->memberLocation)
                const memberPoint = [{{ $assignment->member->memberLocation->latitude }}, {{ $assignment->member->memberLocation->longitude }}];
                L.circleMarker(memberPoint, { radius: 9, color: '#16a34a', fillColor: '#22c55e', fillOpacity: .9 }).addTo(map).bindPopup('Posisi saya');
            @endif
            @if($assignment->route_geometry_json)
                const route = @json($assignment->route_geometry_json);
                const latLngs = route.coordinates.map((point) => [point[1], point[0]]);
                const line = L.polyline(latLngs, { color: '#ef4444', weight: 5 }).addTo(map);
                map.fitBounds(line.getBounds(), { padding: [30, 30] });
            @endif
        </script>
    @endpush
</x-layouts.app>
