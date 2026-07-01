<x-layouts.app title="Lapor Darurat TIMSAR">
    <section class="mx-auto max-w-4xl">
        <div>
            <p class="text-sm font-black uppercase text-red-600">Laporan masyarakat</p>
            <h1 class="mt-2 max-w-3xl text-3xl font-black leading-tight md:text-5xl">Kirim laporan darurat dengan lokasi GPS</h1>
            <p class="mt-3 max-w-2xl text-slate-600">Aktifkan lokasi HP agar posko dapat melihat titik kejadian dan menugaskan anggota TIMSAR terdekat.</p>

            <form method="POST" action="{{ route('public.report.store') }}" class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-sm font-bold">Nama pelapor</label>
                        <input name="reporter_name" value="{{ old('reporter_name') }}" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3" required>
                    </div>
                    <div>
                        <label class="text-sm font-bold">Nomor HP</label>
                        <input name="reporter_phone" value="{{ old('reporter_phone') }}" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3" required>
                    </div>
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-sm font-bold">Jenis kejadian</label>
                        <select name="incident_type" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3" required>
                            <option value="Kecelakaan">Kecelakaan</option>
                            <option value="Orang hilang">Orang hilang</option>
                            <option value="Pendaki cedera">Pendaki cedera</option>
                            <option value="Banjir">Banjir</option>
                            <option value="Kebakaran">Kebakaran</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-bold">Prioritas</label>
                        <select name="priority" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3">
                            <option value="high">Tinggi</option>
                            <option value="critical">Kritis</option>
                            <option value="medium">Sedang</option>
                            <option value="low">Rendah</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="text-sm font-bold">Deskripsi kejadian</label>
                    <textarea name="description" rows="4" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3" required>{{ old('description') }}</textarea>
                </div>

                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <input type="hidden" name="accuracy" id="accuracy">

                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-black">Lokasi GPS</p>
                            <p id="locationText" class="text-sm text-slate-500">Lokasi belum diambil.</p>
                        </div>
                        <button type="button" id="locateBtn" class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-black text-white">Aktifkan Lokasi</button>
                    </div>
                    <div id="map" class="mt-4 h-72 rounded-xl"></div>
                </div>

                <button id="submitBtn" class="mt-5 w-full rounded-xl bg-red-600 px-4 py-4 font-black text-white shadow-sm hover:bg-red-700" disabled>Kirim Laporan Darurat</button>
            </form>
        </div>

    </section>

    @push('scripts')
        <script>
            const defaultPoint = [-8.5833, 116.1167];
            const map = L.map('map').setView(defaultPoint, 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
            let marker = L.marker(defaultPoint).addTo(map);

            document.getElementById('locateBtn').addEventListener('click', () => {
                if (!navigator.geolocation) {
                    document.getElementById('locationText').textContent = 'Browser tidak mendukung GPS.';
                    return;
                }
                document.getElementById('locationText').textContent = 'Mengambil lokasi perangkat...';
                navigator.geolocation.getCurrentPosition((pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    const acc = pos.coords.accuracy;
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lng;
                    document.getElementById('accuracy').value = acc;
                    document.getElementById('locationText').textContent = `Lokasi aktif. Akurasi sekitar ${Math.round(acc)} meter.`;
                    document.getElementById('submitBtn').disabled = false;
                    marker.setLatLng([lat, lng]);
                    map.setView([lat, lng], 16);
                }, () => {
                    document.getElementById('locationText').textContent = 'Gagal mengambil lokasi. Izinkan akses lokasi di browser.';
                }, { enableHighAccuracy: true, timeout: 12000 });
            });
        </script>
    @endpush
</x-layouts.app>
