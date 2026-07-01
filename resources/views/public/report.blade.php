<x-layouts.app title="Lapor Darurat TIMSAR">
    <section class="mx-auto max-w-4xl">
        <div>
            <p class="text-sm font-black uppercase text-red-600">Laporan masyarakat</p>
            <h1 class="mt-2 max-w-3xl text-3xl font-black leading-tight md:text-5xl">Butuh bantuan TIMSAR?</h1>
            <p class="mt-3 max-w-2xl text-slate-600">Isi laporan, aktifkan lokasi, lalu kirim. Posko akan melihat titik kejadian dan menugaskan anggota terdekat.</p>

            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-red-600 text-sm font-black text-white">1</span>
                    <p class="mt-3 font-black">Isi kejadian</p>
                    <p class="mt-1 text-sm text-slate-500">Masukkan nama, nomor HP, dan kondisi darurat.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-red-600 text-sm font-black text-white">2</span>
                    <p class="mt-3 font-black">Aktifkan lokasi</p>
                    <p class="mt-1 text-sm text-slate-500">Izinkan GPS agar titik kejadian tepat.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-red-600 text-sm font-black text-white">3</span>
                    <p class="mt-3 font-black">Kirim laporan</p>
                    <p class="mt-1 text-sm text-slate-500">Simpan kode tracking setelah laporan terkirim.</p>
                </div>
            </div>

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
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-black">Lokasi kejadian</p>
                                <span id="locationBadge" class="rounded-full bg-slate-200 px-3 py-1 text-xs font-black text-slate-600">Belum aktif</span>
                            </div>
                            <p id="locationText" class="mt-1 text-sm text-slate-500">Tekan tombol di kanan, lalu izinkan akses lokasi di HP.</p>
                        </div>
                        <button type="button" id="locateBtn" class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-black text-white">Aktifkan Lokasi</button>
                    </div>
                    <div id="locationHint" class="mt-3 rounded-xl bg-white px-4 py-3 text-sm font-semibold text-slate-600">
                        Tips: gunakan Chrome/Safari, aktifkan lokasi presisi, dan tunggu beberapa detik sampai akurasi membaik.
                    </div>
                    <div id="map" class="mt-4 h-72 rounded-xl"></div>
                </div>

                <button id="submitBtn" class="mt-5 w-full rounded-xl bg-slate-300 px-4 py-4 font-black text-slate-500 shadow-sm" disabled>Aktifkan lokasi dulu untuk mengirim laporan</button>
            </form>
        </div>

    </section>

    @push('scripts')
        <script>
            const defaultPoint = [-8.5833, 116.1167];
            const map = L.map('map').setView(defaultPoint, 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
            let marker = L.marker(defaultPoint).addTo(map);
            let locationWatchId = null;
            let bestPosition = null;
            let watchStartedAt = null;
            const targetAccuracyMeters = 50;
            const maxWatchMs = 120000;
            const locateBtn = document.getElementById('locateBtn');
            const submitBtn = document.getElementById('submitBtn');
            const locationText = document.getElementById('locationText');
            const locationBadge = document.getElementById('locationBadge');
            const locationHint = document.getElementById('locationHint');

            locateBtn.addEventListener('click', () => {
                if (!navigator.geolocation) {
                    setLocationState('error', 'Browser tidak mendukung GPS.');
                    return;
                }
                submitBtn.disabled = true;
                submitBtn.textContent = 'Menunggu lokasi GPS...';
                submitBtn.className = 'mt-5 w-full rounded-xl bg-slate-300 px-4 py-4 font-black text-slate-500 shadow-sm';
                setLocationState('loading', 'Mencari GPS terbaik, tunggu beberapa detik...');
                locateBtn.textContent = 'Mengunci GPS...';
                locateBtn.disabled = true;
                locateBtn.className = 'rounded-xl bg-amber-500 px-4 py-3 text-sm font-black text-white';

                if (locationWatchId !== null) {
                    navigator.geolocation.clearWatch(locationWatchId);
                }

                bestPosition = null;
                watchStartedAt = Date.now();

                locationWatchId = navigator.geolocation.watchPosition((pos) => {
                    if (!bestPosition || pos.coords.accuracy < bestPosition.coords.accuracy) {
                        bestPosition = pos;
                        applyPosition(pos, false);
                    }

                    const waitedMs = Date.now() - watchStartedAt;
                    if (bestPosition.coords.accuracy <= targetAccuracyMeters || waitedMs >= maxWatchMs) {
                        navigator.geolocation.clearWatch(locationWatchId);
                        locationWatchId = null;
                        applyPosition(bestPosition, true);
                    }
                }, (error) => {
                    if (locationWatchId !== null) {
                        navigator.geolocation.clearWatch(locationWatchId);
                        locationWatchId = null;
                    }
                    if (bestPosition) {
                        applyPosition(bestPosition, true);
                        return;
                    }
                    setLocationState('error', geolocationErrorMessage(error));
                    locateBtn.textContent = 'Coba Lagi';
                    locateBtn.disabled = false;
                    locateBtn.className = 'rounded-xl bg-red-600 px-4 py-3 text-sm font-black text-white';
                }, { enableHighAccuracy: true, timeout: 30000, maximumAge: 0 });
            });

            function applyPosition(pos, finalPosition) {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                const acc = pos.coords.accuracy;
                document.getElementById('latitude').value = lat;
                document.getElementById('longitude').value = lng;
                document.getElementById('accuracy').value = acc;
                submitBtn.disabled = false;
                submitBtn.textContent = 'Kirim Laporan Darurat';
                submitBtn.className = 'mt-5 w-full rounded-xl bg-red-600 px-4 py-4 font-black text-white shadow-sm hover:bg-red-700';
                marker.setLatLng([lat, lng]);
                map.setView([lat, lng], acc <= 80 ? 17 : 15);

                if (finalPosition) {
                    locateBtn.textContent = 'Perbarui Lokasi';
                    locateBtn.disabled = false;
                    locateBtn.className = 'rounded-xl bg-emerald-600 px-4 py-3 text-sm font-black text-white';
                    setLocationState(
                        acc > 100 ? 'warning' : 'ready',
                        acc > 100
                            ? `Lokasi aktif, tetapi akurasi masih sekitar ${Math.round(acc)} meter. Laporan tetap bisa dikirim, atau tekan Perbarui Lokasi jika ingin mencoba lebih presisi.`
                            : `Lokasi aktif. Akurasi sekitar ${Math.round(acc)} meter.`
                    );
                    return;
                }

                const waitedSeconds = watchStartedAt ? Math.round((Date.now() - watchStartedAt) / 1000) : 0;
                setLocationState('loading', `Mengunci GPS... akurasi terbaik sementara ${Math.round(acc)} meter (${waitedSeconds}s).`);
            }

            function setLocationState(state, message) {
                locationText.textContent = message;

                const states = {
                    idle: ['Belum aktif', 'bg-slate-200 text-slate-600'],
                    loading: ['Mengunci GPS', 'bg-amber-100 text-amber-800'],
                    ready: ['Lokasi aktif', 'bg-emerald-100 text-emerald-700'],
                    warning: ['Akurasi rendah', 'bg-yellow-100 text-yellow-800'],
                    error: ['Perlu izin', 'bg-red-100 text-red-700'],
                };
                const [label, className] = states[state] ?? states.idle;
                locationBadge.textContent = label;
                locationBadge.className = `rounded-full px-3 py-1 text-xs font-black ${className}`;
                locationHint.className = state === 'ready'
                    ? 'mt-3 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800'
                    : state === 'warning'
                        ? 'mt-3 rounded-xl bg-yellow-50 px-4 py-3 text-sm font-semibold text-yellow-800'
                        : state === 'error'
                            ? 'mt-3 rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-800'
                            : 'mt-3 rounded-xl bg-white px-4 py-3 text-sm font-semibold text-slate-600';
            }

            function geolocationErrorMessage(error) {
                if (error.code === error.PERMISSION_DENIED) {
                    return 'Izin lokasi ditolak. Buka pengaturan browser, izinkan lokasi untuk situs TIMSAR, lalu coba lagi.';
                }
                if (error.code === error.POSITION_UNAVAILABLE) {
                    return 'Lokasi belum tersedia. Pastikan GPS HP aktif, mode lokasi presisi menyala, dan coba di area terbuka.';
                }
                if (error.code === error.TIMEOUT) {
                    return 'GPS terlalu lama merespons. Coba lagi, aktifkan lokasi presisi, atau pindah ke area yang sinyal GPS-nya lebih baik.';
                }

                return 'Gagal mengambil lokasi. Pastikan GPS dan izin lokasi browser aktif.';
            }
        </script>
    @endpush
</x-layouts.app>
