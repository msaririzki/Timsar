<x-layouts.app title="Lapor Darurat TIMSAR">

    @push('scripts')
        <style>
            /* ── Animations ── */
            @keyframes fadeUp {
                from { opacity: 0; transform: translateY(8px); }
                to   { opacity: 1; transform: translateY(0); }
            }
            @keyframes bounce-dot {
                0%, 80%, 100% { transform: scale(0); }
                40%            { transform: scale(1); }
            }

            .fade-up { animation: fadeUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) both; }
            .delay-1 { animation-delay: .05s; }

            /* ── Custom select wrapper ── */
            .custom-select-wrapper { position: relative; }
            .custom-select-wrapper .select-trigger {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                width: 100%;
                padding: 0.75rem 0.875rem;
                border-radius: 0.5rem;
                border: 1px solid #cbd5e1;
                background: #fff;
                cursor: pointer;
                user-select: none;
                transition: all 0.15s ease;
                font-size: 0.875rem;
                font-weight: 500;
                color: #334155;
            }
            .custom-select-wrapper .select-trigger:hover,
            .custom-select-wrapper.open .select-trigger {
                border-color: #dc2626;
                box-shadow: 0 0 0 2.5px rgba(220, 38, 38, 0.08);
            }
            .custom-select-wrapper .select-arrow {
                margin-left: auto;
                transition: transform 0.2s;
                color: #64748b;
            }
            .custom-select-wrapper.open .select-arrow { transform: rotate(180deg); }

            .custom-select-wrapper .select-dropdown {
                position: absolute;
                top: calc(100% + 4px);
                left: 0; right: 0;
                background: #fff;
                border: 1px solid #cbd5e1;
                border-radius: 0.5rem;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
                max-height: 220px;
                overflow-y: auto;
                z-index: 50;
                opacity: 0;
                transform: translateY(-4px) scale(.99);
                pointer-events: none;
                transition: opacity 0.15s, transform 0.15s ease;
            }
            .custom-select-wrapper.open .select-dropdown {
                opacity: 1;
                transform: translateY(0) scale(1);
                pointer-events: auto;
            }
            .custom-select-wrapper .select-option {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.625rem 0.875rem;
                cursor: pointer;
                font-size: 0.875rem;
                font-weight: 500;
                color: #475569;
                transition: background 0.1s ease;
            }
            .custom-select-wrapper .select-option:hover  { background: #fef2f2; color: #dc2626; }
            .custom-select-wrapper .select-option.active { background: #fef2f2; color: #dc2626; }

            /* ── Inputs & Labels ── */
            .form-input {
                width: 100%;
                padding: 0.75rem 0.875rem;
                border-radius: 0.5rem;
                border: 1px solid #cbd5e1;
                font-size: 0.875rem;
                font-weight: 500;
                color: #1e293b;
                background: #fff;
                transition: all 0.15s ease;
                outline: none;
            }
            .form-input:focus {
                border-color: #dc2626;
                box-shadow: 0 0 0 2.5px rgba(220, 38, 38, 0.08);
            }
            .form-input.is-valid {
                border-color: #10b981;
                background-color: #f0fdf4;
            }
            .form-label {
                display: block;
                font-size: 0.75rem;
                font-weight: 700;
                color: #475569;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                margin-bottom: 0.375rem;
            }

            /* ── Step Tracker ── */
            .step-dot {
                width: 1.125rem;
                height: 1.125rem;
                border-radius: 9999px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.65rem;
                font-weight: 800;
                transition: all 0.2s ease;
            }

            /* ── Locate Button ── */
            #locateBtn {
                display: inline-flex;
                align-items: center;
                gap: 0.375rem;
                border-radius: 0.5rem;
                padding: 0.5rem 0.875rem;
                font-size: 0.825rem;
                font-weight: 700;
                color: #fff;
                background: #1e293b;
                transition: all 0.15s ease;
                border: none;
                cursor: pointer;
            }
            #locateBtn:hover:not(:disabled) {
                background: #334155;
            }
            #locateBtn.state-loading  { background: #d97706; }
            #locateBtn.state-ready    { background: #059669; }
            #locateBtn.state-error    { background: #dc2626; }

            /* ── Loading Spinner for Submit ── */
            .dot-loader span {
                display: inline-block;
                width: 4px; height: 4px;
                margin: 0 1px;
                border-radius: 50%;
                background: currentColor;
                animation: bounce-dot 1.4s infinite ease-in-out both;
            }
            .dot-loader span:nth-child(1) { animation-delay: -.32s; }
            .dot-loader span:nth-child(2) { animation-delay: -.16s; }

            /* ── Submit Button ── */
            #submitBtn {
                width: 100%;
                padding: 0.75rem 1.25rem;
                border-radius: 0.5rem;
                font-size: 0.875rem;
                font-weight: 700;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.375rem;
                cursor: pointer;
                border: none;
            }
            #submitBtn.gps-pending-state {
                background: #ea580c;
                color: #fff;
            }
            #submitBtn.gps-pending-state:hover {
                background: #c2410c;
            }
            #submitBtn.ready-state {
                background: #dc2626;
                color: #fff;
            }
            #submitBtn.ready-state:hover {
                background: #b91c1c;
            }
            #submitBtn.disabled-state {
                background: #e2e8f0;
                color: #94a3b8;
                cursor: not-allowed;
            }

            /* ── GPS Status Alert card ── */
            .gps-status-card {
                border-radius: 0.375rem;
                padding: 0.625rem 0.75rem;
                font-size: 0.8rem;
                font-weight: 500;
                transition: all 0.2s ease;
            }
            .gps-idle    { background:#f8fafc; color:#64748b; border: 1px solid #e2e8f0; }
            .gps-loading { background:#fffbeb; color:#92400e; border: 1px solid #fde68a; }
            .gps-ready   { background:#f0fdf4; color:#065f46; border: 1px solid #a7f3d0; }
            .gps-warning { background:#fefce8; color:#854d0e; border: 1px solid #fef08a; }
            .gps-error   { background:#fef2f2; color:#991b1b; border: 1px solid #fecaca; }
        </style>
    @endpush

    <div class="mx-auto max-w-xl px-2 sm:px-0">

        {{-- ── COMPACT TITLE & DESCRIPTION ── --}}
        <div class="mb-4 mt-2 fade-up">
            <span class="inline-flex items-center gap-1 rounded bg-red-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-red-600 border border-red-100">Lapor Darurat TIMSAR</span>
            <h1 class="mt-1 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                Formulir Laporan Darurat
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Isi detail kejadian. Sistem akan otomatis melacak GPS perangkat Anda untuk pengiriman bantuan cepat.
            </p>
        </div>

        {{-- ── FORM CARD ── --}}
        <form
            method="POST"
            action="{{ route('public.report.store') }}"
            class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden fade-up delay-1"
            id="reportForm"
            novalidate
        >
            @csrf

            {{-- Compact Step Header --}}
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-4 py-2.5 text-xs font-bold text-slate-500">
                <div class="flex items-center gap-1.5" id="step1">
                    <span class="step-dot bg-slate-200 text-slate-600" id="stepNum1">1</span>
                    <span>Data Kejadian</span>
                </div>
                <div class="h-px bg-slate-200 flex-1 mx-3"></div>
                <div class="flex items-center gap-1.5" id="step2">
                    <span class="step-dot bg-slate-200 text-slate-600" id="stepNum2">2</span>
                    <span>Lokasi</span>
                </div>
            </div>

            <div class="p-4 space-y-4">

                {{-- ── Nama Pelapor & Nomor HP ── --}}
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="reporter_name">Nama Pelapor <span class="text-red-500">*</span></label>
                        <input
                            id="reporter_name"
                            name="reporter_name"
                            type="text"
                            value="{{ old('reporter_name') }}"
                            placeholder="Nama lengkap Anda"
                            class="form-input"
                            required
                        >
                    </div>
                    <div>
                        <label class="form-label" for="reporter_phone">Nomor HP / WA <span class="text-red-500">*</span></label>
                        <input
                            id="reporter_phone"
                            name="reporter_phone"
                            type="tel"
                            value="{{ old('reporter_phone') }}"
                            placeholder="Contoh: 081234567890"
                            inputmode="tel"
                            autocomplete="tel"
                            maxlength="17"
                            aria-describedby="phoneHelp"
                            class="form-input @error('reporter_phone') border-red-400 @enderror"
                            required
                        >
                        <p id="phoneHelp" class="mt-1 text-[11px] text-slate-500">Gunakan format 08 atau +62. Spasi dan tanda hubung diperbolehkan.</p>
                        @error('reporter_phone')
                            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- ── Jenis Kejadian & Tingkat Prioritas ── --}}
                <div>
                    <div>
                        <label class="form-label">Jenis Kejadian <span class="text-red-500">*</span></label>
                        <div class="custom-select-wrapper" id="incidentWrapper">
                            <div class="select-trigger" id="incidentTrigger">
                                <span id="incidentIcon">!</span>
                                <span id="incidentLabel">Pilih jenis kejadian</span>
                                <svg class="select-arrow h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                            </div>
                            <div class="select-dropdown" id="incidentDropdown">
                                <div class="select-option" data-value="Kecelakaan" data-icon="1">1&nbsp; Kecelakaan</div>
                                <div class="select-option" data-value="Orang hilang" data-icon="2">2&nbsp; Orang Hilang</div>
                                <div class="select-option" data-value="Pendaki cedera" data-icon="3">3&nbsp; Pendaki Cedera</div>
                                <div class="select-option" data-value="Banjir" data-icon="4">4&nbsp; Banjir</div>
                                <div class="select-option" data-value="Kebakaran" data-icon="5">5&nbsp; Kebakaran</div>
                                <div class="select-option" data-value="Lainnya" data-icon="6">6&nbsp; Lainnya</div>
                            </div>
                            <input type="hidden" name="incident_type" id="incidentValue" value="{{ old('incident_type') }}" required>
                        </div>
                    </div>
                    <input type="hidden" name="priority" value="{{ old('priority', 'high') }}">
                </div>

                {{-- ── Deskripsi Kejadian ── --}}
                <div>
                    <label class="form-label" for="description">Deskripsi Kejadian <span class="text-red-500">*</span></label>
                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        placeholder="Tulis kronologi singkat, estimasi jumlah korban, dan kondisi saat ini"
                        class="form-input resize-none"
                        required
                    >{{ old('description') }}</textarea>
                    <div class="mt-1 flex justify-between items-center text-[10px] text-slate-400">
                        <span>Jelaskan situasi sejelas mungkin.</span>
                        <span id="charCount">0 / 2000 karakter</span>
                    </div>
                </div>

                {{-- ── GPS & Map Section (Compact) ── --}}
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 transition-all duration-300" id="locationSection">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex-1 min-w-[200px]">
                            <div class="flex items-center gap-1.5">
                                <span id="locationBadge" class="rounded px-1.5 py-0.5 text-[9px] font-bold bg-slate-200 text-slate-600">GPS BELUM AKTIF</span>
                            </div>
                            <p id="locationText" class="mt-1 text-[11px] text-slate-500">
                                Koordinat lokasi presisi mempercepat kedatangan tim penyelamat.
                            </p>
                        </div>
                        <button type="button" id="locateBtn" class="w-full sm:w-auto">
                            <svg id="locateIcon" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                            <span id="locateBtnText">Ambil Lokasi Saya</span>
                        </button>
                    </div>

                    <div id="locationHint" class="gps-status-card gps-idle mt-2.5 flex items-start gap-1.5">
                        <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                        <span id="locationHintText">Rekomendasi: aktifkan GPS perangkat Anda untuk akurasi terbaik.</span>
                    </div>

                    <div id="map" class="mt-2.5 h-40 sm:h-48 rounded border border-slate-200 overflow-hidden"></div>
                </div>

                {{-- Hidden Inputs --}}
                <input type="hidden" name="latitude"  id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <input type="hidden" name="accuracy"  id="accuracy">
                <div id="reportSummary" class="hidden rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-900">
                    <div class="font-bold">Data siap dikirim</div>
                    <div class="mt-2 grid gap-1 sm:grid-cols-3">
                        <div>
                            <div class="text-[10px] uppercase text-emerald-700">Nomor HP</div>
                            <div id="summaryPhone" class="font-semibold">-</div>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase text-emerald-700">Koordinat</div>
                            <div id="summaryLocation" class="font-semibold">-</div>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase text-emerald-700">Akurasi</div>
                            <div id="summaryAccuracy" class="font-semibold">-</div>
                        </div>
                    </div>
                </div>
                <button type="submit" id="submitBtn" class="gps-pending-state">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                    <span id="submitBtnText">Kirim Laporan Darurat</span>
                </button>

            </div>
        </form>

        {{-- Disclaimer --}}
        <p class="mt-3 text-center text-[10px] text-slate-400 fade-up delay-2">
            Penyalahgunaan sistem ini diancam sanksi hukum. Harap gunakan hanya untuk situasi darurat.
        </p>
    </div>

    @push('scripts')
    <script>
    /* ═══════════════ MAP INIT ═══════════════ */
    const defaultPoint = [-8.5833, 116.1167];
    const map = L.map('map').setView(defaultPoint, 13);
    TimsarMap.addTiles(map);
    let marker = L.marker(defaultPoint, { icon: TimsarMap.icon('user') }).addTo(map).bindPopup('Lokasi laporan Anda');

    let locationWatchId   = null;
    let bestPosition      = null;
    let watchStartedAt    = null;
    const targetAccuracy  = 50;
    const maxWatchMs      = 25000;
    let submitAfterLocate = false;

    /* ── Refs ── */
    const reportForm     = document.getElementById('reportForm');
    const locateBtn      = document.getElementById('locateBtn');
    const locateBtnText  = document.getElementById('locateBtnText');
    const locateIcon     = document.getElementById('locateIcon');
    const submitBtn      = document.getElementById('submitBtn');
    const locationText   = document.getElementById('locationText');
    const locationBadge  = document.getElementById('locationBadge');
    const locationHint   = document.getElementById('locationHint');
    const locationHintTx = document.getElementById('locationHintText');
    const locationSection= document.getElementById('locationSection');
    const reportSummary  = document.getElementById('reportSummary');
    const summaryPhone   = document.getElementById('summaryPhone');
    const summaryLocation= document.getElementById('summaryLocation');
    const summaryAccuracy= document.getElementById('summaryAccuracy');

    /* ── Fields & Steps ── */
    const nameInput = document.getElementById('reporter_name');
    const phoneInput = document.getElementById('reporter_phone');
    const descTa = document.getElementById('description');
    const incidentValue = document.getElementById('incidentValue');

    const step1 = document.getElementById('step1');
    const stepNum1 = document.getElementById('stepNum1');
    const step2 = document.getElementById('step2');
    const stepNum2 = document.getElementById('stepNum2');

    function normalizePhone(value) {
        let phone = value.trim().replace(/[^0-9+]/g, '');
        if (phone.startsWith('+62')) phone = `0${phone.slice(3)}`;
        else if (phone.startsWith('62')) phone = `0${phone.slice(2)}`;
        else if (phone.startsWith('8')) phone = `0${phone}`;
        return phone;
    }

    function validatePhone() {
        const normalized = normalizePhone(phoneInput.value);
        const valid = /^08[0-9]{8,11}$/.test(normalized);
        phoneInput.setCustomValidity(phoneInput.value.trim() !== '' && !valid
            ? 'Masukkan nomor HP Indonesia yang valid, misalnya 081234567890 atau +6281234567890.'
            : '');
        return valid;
    }

    function updateReportSummary(pos = bestPosition) {
        if (!reportSummary || !pos) return;

        const { latitude: lat, longitude: lng, accuracy: acc } = pos.coords;
        summaryPhone.textContent = normalizePhone(phoneInput.value) || '-';
        summaryLocation.textContent = `${Number(lat).toFixed(5)}, ${Number(lng).toFixed(5)}`;
        summaryAccuracy.textContent = `${Math.round(acc)} m`;
        reportSummary.classList.remove('hidden');
    }
    function checkFormValidity() {
        let step1Completed = nameInput.value.trim() !== '' &&
                             validatePhone() &&
                             descTa.value.trim() !== '' &&
                             incidentValue.value !== '';

        if (step1Completed) {
            step1.classList.add('text-emerald-600');
            stepNum1.className = 'step-dot bg-emerald-500 text-white';
            stepNum1.innerHTML = 'OK';
        } else {
            step1.classList.remove('text-emerald-600');
            stepNum1.className = 'step-dot bg-slate-200 text-slate-600';
            stepNum1.innerHTML = '1';
        }

        [nameInput, phoneInput, descTa].forEach(el => {
            if (el.value.trim() !== '') {
                el.classList.add('is-valid');
            } else {
                el.classList.remove('is-valid');
            }
        });
    }

    [nameInput, phoneInput, descTa].forEach(input => {
        input.addEventListener('input', checkFormValidity);
    });
    phoneInput.addEventListener('blur', () => {
        const normalized = normalizePhone(phoneInput.value);
        if (/^08[0-9]{8,11}$/.test(normalized)) phoneInput.value = normalized;
        validatePhone();
        if (bestPosition) updateReportSummary(bestPosition);
    });

    /* ═══════════════ GPS LOCATE ═══════════════ */
    function requestLocation() {
        if (!navigator.geolocation) {
            setGPSState('error', 'Browser tidak mendukung deteksi lokasi.');
            return;
        }

        setLocateBtnState('loading');
        setGPSState('loading', 'Mengunci lokasi Anda. Harap tunggu sebentar.');

        if (locationWatchId !== null) navigator.geolocation.clearWatch(locationWatchId);
        bestPosition  = null;
        watchStartedAt = Date.now();

        locationWatchId = navigator.geolocation.watchPosition(
            (pos) => {
                if (!bestPosition || pos.coords.accuracy < bestPosition.coords.accuracy) {
                    bestPosition = pos;
                    applyPosition(pos, false);
                }
                const waited = Date.now() - watchStartedAt;
                if (bestPosition.coords.accuracy <= targetAccuracy || waited >= maxWatchMs) {
                    navigator.geolocation.clearWatch(locationWatchId);
                    locationWatchId = null;
                    applyPosition(bestPosition, true);
                }
            },
            (err) => {
                if (locationWatchId !== null) {
                    navigator.geolocation.clearWatch(locationWatchId);
                    locationWatchId = null;
                }
                if (bestPosition) {
                    applyPosition(bestPosition, true);
                    return;
                }
                setGPSState('error', geolocationErrorMessage(err));
                setLocateBtnState('error');
                submitAfterLocate = false;
                setSubmitBtnState('idle');
            },
            { enableHighAccuracy: true, timeout: 25000, maximumAge: 0 }
        );
    }

    locateBtn.addEventListener('click', requestLocation);

    function applyPosition(pos, isFinal) {
        const { latitude: lat, longitude: lng, accuracy: acc } = pos.coords;
        document.getElementById('latitude').value  = lat;
        document.getElementById('longitude').value = lng;
        document.getElementById('accuracy').value  = acc;

        TimsarMap.moveMarker(marker, [lat, lng]);
        map.setView([lat, lng], acc <= 80 ? 17 : 15, { animate: true });
        locationSection.style.borderColor = isFinal ? '#86efac' : '#fde68a';

        if (isFinal) {
            setLocateBtnState('ready');

            step2.classList.add('text-emerald-600');
            stepNum2.className = 'step-dot bg-emerald-500 text-white';
            stepNum2.innerHTML = 'OK';

            const state = acc > 100 ? 'warning' : 'ready';
            const msg = acc > 100
                ? `GPS Aktif (Akurasi sekitar ${Math.round(acc)}m).`
                : `GPS Aktif (Akurasi sekitar ${Math.round(acc)}m).`;
            setGPSState(state, msg);
            updateReportSummary(pos);
            setSubmitBtnState('ready');

            if (submitAfterLocate) {
                submitFormDirect();
            }
        } else {
            const waited = watchStartedAt ? Math.round((Date.now() - watchStartedAt) / 1000) : 0;
            setGPSState('loading', `Mengunci GPS... Akurasi sekitar ${Math.round(acc)}m (${waited}s)`);
            if (submitAfterLocate) {
                setSubmitBtnState('gps_loading');
            }
        }
    }

    function setLocateBtnState(state) {
        locateBtn.className = 'w-full sm:w-auto';
        locateBtn.disabled  = (state === 'loading');

        if (state === 'loading') {
            locateBtn.className = 'state-loading w-full sm:w-auto';
            locateBtnText.textContent = 'Mengambil lokasi';
        } else if (state === 'ready') {
            locateBtn.className = 'state-ready w-full sm:w-auto';
            locateBtnText.textContent = 'Perbarui GPS';
        } else if (state === 'error') {
            locateBtn.className = 'state-error w-full sm:w-auto';
            locateBtnText.textContent = 'Coba Lagi';
        } else {
            locateBtnText.textContent = 'Ambil Lokasi Saya';
        }
    }

    function setGPSState(state, msg) {
        locationText.textContent = msg;
        const labels = {
            idle:    ['Belum Aktif',   'bg-slate-200 text-slate-600'],
            loading: ['Mencari GPS',   'bg-amber-100 text-amber-800'],
            ready:   ['GPS Aktif',     'bg-emerald-100 text-emerald-700'],
            warning: ['Akurasi Rendah','bg-yellow-100 text-yellow-800'],
            error:   ['GPS Error',     'bg-red-100 text-red-700'],
        };
        const [label, cls] = labels[state] ?? labels.idle;
        locationBadge.textContent  = label;
        locationBadge.className    = `rounded px-1.5 py-0.5 text-[9px] font-bold ${cls}`;

        const hintClasses = {
            loading: 'gps-status-card gps-loading mt-2.5 flex items-start gap-1.5',
            ready:   'gps-status-card gps-ready mt-2.5 flex items-start gap-1.5',
            warning: 'gps-status-card gps-warning mt-2.5 flex items-start gap-1.5',
            error:   'gps-status-card gps-error mt-2.5 flex items-start gap-1.5',
        };
        locationHint.className = hintClasses[state] ?? 'gps-status-card gps-idle mt-2.5 flex items-start gap-1.5';
        locationHintTx.textContent = msg;
    }

    function setSubmitBtnState(state) {
        if (state === 'ready') {
            submitBtn.className = 'ready-state';
            submitBtn.disabled = false;
            submitBtn.innerHTML = `
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.769 59.769 0 0 1 3.269 20.875L5.999 12Zm0 0h7.5"/></svg>
                <span>Kirim Laporan Darurat</span>
            `;
        } else if (state === 'submitting') {
            submitBtn.className = 'disabled-state';
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <div class="dot-loader" style="color:#64748b">
                    <span></span><span></span><span></span>
                </div>
                <span>Mengirim laporan...</span>
            `;
        } else if (state === 'gps_loading') {
            submitBtn.className = 'disabled-state';
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <div class="dot-loader" style="color:#64748b">
                    <span></span><span></span><span></span>
                </div>
                <span>Mengunci lokasi...</span>
            `;
        } else {
            submitBtn.className = 'gps-pending-state';
            submitBtn.disabled = false;
            submitBtn.innerHTML = `
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                <span>Kirim Laporan Darurat</span>
            `;
        }
    }

    /* ═══════════════ SUBMIT BEHAVIOR ═══════════════ */
    reportForm.addEventListener('submit', (e) => {
        e.preventDefault();

        phoneInput.value = normalizePhone(phoneInput.value);
        validatePhone();

        if (!reportForm.checkValidity()) {
            reportForm.reportValidity();
            return;
        }

        if (incidentValue.value === '') {
            const trigger = document.getElementById('incidentTrigger');
            trigger.style.borderColor = '#ef4444';
            trigger.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const lat = document.getElementById('latitude').value;

        if (!lat) {
            submitAfterLocate = true;
            setSubmitBtnState('gps_loading');
            requestLocation();
            locationSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            submitFormDirect();
        }
    });

    function submitFormDirect() {
        if (bestPosition) updateReportSummary(bestPosition);
        setSubmitBtnState('submitting');
        reportForm.submit();
    }

    /* ═══════════════ DROPDOWNS ═══════════════ */
    function initDropdown(wrapperId, triggerId, dropdownId, valueId, labelId, iconId) {
        const wrapper  = document.getElementById(wrapperId);
        const trigger  = document.getElementById(triggerId);
        const dropdown = document.getElementById(dropdownId);
        const hidInput = document.getElementById(valueId);
        const label    = document.getElementById(labelId);
        const icon     = document.getElementById(iconId);

        const oldVal = hidInput.value;
        if (oldVal) {
            const opt = dropdown.querySelector(`[data-value="${oldVal}"]`);
            if (opt) {
                label.textContent = opt.textContent.replace(opt.dataset.icon, '').trim();
                icon.textContent = opt.dataset.icon;
                opt.classList.add('active');
            }
        }

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = wrapper.classList.contains('open');
            document.querySelectorAll('.custom-select-wrapper.open').forEach(w => w.classList.remove('open'));
            if (!isOpen) {
                wrapper.classList.add('open');
            }
        });

        function closeDropdown() {
            wrapper.classList.remove('open');
        }

        dropdown.querySelectorAll('.select-option').forEach(opt => {
            opt.addEventListener('click', (e) => {
                e.stopPropagation();
                hidInput.value = opt.dataset.value;
                label.textContent = opt.textContent.replace(opt.dataset.icon, '').trim();
                icon.textContent  = opt.dataset.icon;
                dropdown.querySelectorAll('.select-option').forEach(o => o.classList.remove('active'));
                opt.classList.add('active');
                closeDropdown();

                trigger.style.borderColor = '';
                checkFormValidity();
            });
        });
    }

    initDropdown('incidentWrapper','incidentTrigger','incidentDropdown','incidentValue','incidentLabel','incidentIcon');
    document.addEventListener('click', () => {
        document.querySelectorAll('.custom-select-wrapper.open').forEach(w => {
            w.classList.remove('open');
            document.body.style.overflow = '';
        });
    });

    /* ── Char Count ── */
    descTa.addEventListener('input', () => {
        const n = descTa.value.length;
        charCount.textContent = `${n} / 2000 karakter`;
        charCount.style.color = n > 1800 ? '#ef4444' : n > 1500 ? '#f59e0b' : '#94a3b8';
    });

    checkFormValidity();
    </script>
    @endpush
</x-layouts.app>
