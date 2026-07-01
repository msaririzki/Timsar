<x-layouts.app title="Lapor Darurat TIMSAR">

    @push('scripts')
        <style>
            /* ── Animations ── */
            @keyframes fadeUp {
                from { opacity: 0; transform: translateY(24px); }
                to   { opacity: 1; transform: translateY(0); }
            }
            @keyframes pulse-ring {
                0%   { transform: scale(1);   opacity: .6; }
                70%  { transform: scale(1.55); opacity: 0; }
                100% { transform: scale(1.55); opacity: 0; }
            }
            @keyframes shimmer {
                0%   { background-position: -200% center; }
                100% { background-position:  200% center; }
            }
            @keyframes spin { to { transform: rotate(360deg); } }
            @keyframes bounce-dot {
                0%, 80%, 100% { transform: scale(0); }
                40%            { transform: scale(1); }
            }
            @keyframes gradient-x {
                0%, 100% { background-position: 0% 50%;   }
                50%       { background-position: 100% 50%; }
            }

            /* ── Fade-up delays ── */
            .fade-up { animation: fadeUp .5s ease both; }
            .delay-1 { animation-delay: .08s; }
            .delay-2 { animation-delay: .18s; }
            .delay-3 { animation-delay: .28s; }
            .delay-4 { animation-delay: .38s; }
            .delay-5 { animation-delay: .48s; }

            /* ── Animated gradient header ── */
            .hero-gradient {
                background: linear-gradient(135deg, #1e1b4b 0%, #7f1d1d 40%, #991b1b 70%, #1e1b4b 100%);
                background-size: 300% 300%;
                animation: gradient-x 8s ease infinite;
            }

            /* ── Custom select wrapper ── */
            .custom-select-wrapper { position: relative; }
            .custom-select-wrapper .select-trigger {
                display: flex;
                align-items: center;
                gap: .625rem;
                width: 100%;
                padding: .875rem 1rem;
                border-radius: .875rem;
                border: 1.5px solid #e2e8f0;
                background: #fff;
                cursor: pointer;
                user-select: none;
                transition: border-color .2s, box-shadow .2s;
                font-size: .9rem;
                font-weight: 600;
                color: #1e293b;
            }
            .custom-select-wrapper .select-trigger:hover,
            .custom-select-wrapper.open .select-trigger {
                border-color: #dc2626;
                box-shadow: 0 0 0 3px rgba(220,38,38,.12);
            }
            .custom-select-wrapper .select-arrow {
                margin-left: auto;
                transition: transform .25s;
                color: #64748b;
            }
            .custom-select-wrapper.open .select-arrow { transform: rotate(180deg); }

            .custom-select-wrapper .select-dropdown {
                position: absolute;
                top: calc(100% + 6px);
                left: 0; right: 0;
                background: #fff;
                border: 1.5px solid #e2e8f0;
                border-radius: .875rem;
                box-shadow: 0 12px 40px rgba(0,0,0,.12);
                overflow: hidden;
                z-index: 50;
                opacity: 0;
                transform: translateY(-8px) scale(.98);
                pointer-events: none;
                transition: opacity .2s, transform .2s;
            }
            .custom-select-wrapper.open .select-dropdown {
                opacity: 1;
                transform: translateY(0) scale(1);
                pointer-events: auto;
            }
            .custom-select-wrapper .select-option {
                display: flex;
                align-items: center;
                gap: .625rem;
                padding: .75rem 1rem;
                cursor: pointer;
                font-size: .875rem;
                font-weight: 600;
                color: #334155;
                transition: background .15s;
            }
            .custom-select-wrapper .select-option:hover  { background: #fef2f2; color: #dc2626; }
            .custom-select-wrapper .select-option.active { background: #fef2f2; color: #dc2626; }

            /* ── Input focus ── */
            .form-input {
                width: 100%;
                padding: .875rem 1rem;
                border-radius: .875rem;
                border: 1.5px solid #e2e8f0;
                font-size: .9rem;
                font-weight: 500;
                color: #1e293b;
                background: #fff;
                transition: border-color .2s, box-shadow .2s;
                outline: none;
            }
            .form-input:focus {
                border-color: #dc2626;
                box-shadow: 0 0 0 3px rgba(220,38,38,.12);
            }
            .form-label {
                display: block;
                font-size: .8rem;
                font-weight: 700;
                color: #64748b;
                text-transform: uppercase;
                letter-spacing: .06em;
                margin-bottom: .5rem;
            }

            /* ── Step card ── */
            .step-card {
                background: rgba(255,255,255,.08);
                border: 1px solid rgba(255,255,255,.14);
                border-radius: 1rem;
                padding: 1rem;
                transition: background .2s, transform .2s;
            }
            .step-card:hover {
                background: rgba(255,255,255,.14);
                transform: translateY(-3px);
            }

            /* ── Locate button states ── */
            #locateBtn {
                display: inline-flex;
                align-items: center;
                gap: .5rem;
                border-radius: .875rem;
                padding: .75rem 1.25rem;
                font-size: .875rem;
                font-weight: 700;
                color: #fff;
                background: #1e293b;
                transition: background .2s, transform .15s, box-shadow .2s;
                border: none;
                cursor: pointer;
                white-space: nowrap;
            }
            #locateBtn:hover:not(:disabled) {
                transform: translateY(-1px);
                box-shadow: 0 6px 20px rgba(0,0,0,.2);
            }
            #locateBtn.state-loading  { background: #d97706; }
            #locateBtn.state-ready    { background: #059669; }
            #locateBtn.state-error    { background: #dc2626; }

            /* ── Pulse ring on locate icon ── */
            .pulse-wrap { position: relative; display: inline-flex; }
            .pulse-wrap::before {
                content: '';
                position: absolute;
                inset: -4px;
                border-radius: 9999px;
                background: currentColor;
                animation: pulse-ring 1.6s ease-out infinite;
            }

            /* ── Submit button ── */
            #submitBtn {
                width: 100%;
                padding: 1rem 1.5rem;
                border-radius: .875rem;
                font-size: 1rem;
                font-weight: 800;
                letter-spacing: .04em;
                border: none;
                cursor: pointer;
                transition: all .25s;
                margin-top: 1.25rem;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: .625rem;
            }
            #submitBtn.disabled-state {
                background: #e2e8f0;
                color: #94a3b8;
                cursor: not-allowed;
            }
            #submitBtn.ready-state {
                background: linear-gradient(135deg, #dc2626, #991b1b);
                color: #fff;
                box-shadow: 0 8px 24px rgba(220,38,38,.35);
            }
            #submitBtn.ready-state:hover {
                transform: translateY(-2px);
                box-shadow: 0 12px 30px rgba(220,38,38,.45);
            }
            #submitBtn.ready-state:active { transform: translateY(0); }

            /* ── Loading dots ── */
            .dot-loader span {
                display: inline-block;
                width: 6px; height: 6px;
                margin: 0 2px;
                border-radius: 50%;
                background: currentColor;
                animation: bounce-dot 1.4s infinite ease-in-out both;
            }
            .dot-loader span:nth-child(1) { animation-delay: -.32s; }
            .dot-loader span:nth-child(2) { animation-delay: -.16s; }

            /* ── GPS status card ── */
            .gps-status-card {
                border-radius: .875rem;
                padding: .875rem 1rem;
                font-size: .875rem;
                font-weight: 600;
                transition: background .3s, color .3s;
            }
            .gps-idle    { background:#f1f5f9; color:#64748b; }
            .gps-loading { background:#fffbeb; color:#92400e; }
            .gps-ready   { background:#f0fdf4; color:#065f46; }
            .gps-warning { background:#fefce8; color:#854d0e; }
            .gps-error   { background:#fef2f2; color:#991b1b; }
        </style>
    @endpush

    {{-- ═══════════════════ HERO SECTION ═══════════════════ --}}
    <div class="hero-gradient -mx-4 -mt-6 mb-8 px-4 py-10 fade-up">
        <div class="mx-auto max-w-4xl">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-500/20 px-3 py-1 text-xs font-bold uppercase tracking-widest text-red-300">
                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4"/></svg>
                Laporan Masyarakat
            </span>
            <h1 class="mt-3 text-3xl font-black leading-tight text-white md:text-5xl">
                Butuh bantuan <span class="text-red-400">TIMSAR</span>?
            </h1>
            <p class="mt-3 max-w-xl text-base text-white/70">
                Isi laporan, aktifkan lokasi GPS, lalu kirim. Posko akan melihat titik kejadian dan menugaskan anggota terdekat ke lokasi kamu.
            </p>

            {{-- Step cards --}}
            <div class="mt-7 grid gap-3 sm:grid-cols-3">
                <div class="step-card fade-up delay-2">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-red-500 text-sm font-black text-white shadow-lg">1</span>
                    <p class="mt-3 font-bold text-white">Isi kejadian</p>
                    <p class="mt-1 text-sm text-white/60">Nama, nomor HP, jenis, dan deskripsi darurat.</p>
                </div>
                <div class="step-card fade-up delay-3">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-red-500 text-sm font-black text-white shadow-lg">2</span>
                    <p class="mt-3 font-bold text-white">Aktifkan lokasi</p>
                    <p class="mt-1 text-sm text-white/60">Izinkan GPS agar titik kejadian tepat.</p>
                </div>
                <div class="step-card fade-up delay-4">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-red-500 text-sm font-black text-white shadow-lg">3</span>
                    <p class="mt-3 font-bold text-white">Kirim laporan</p>
                    <p class="mt-1 text-sm text-white/60">Simpan kode tracking setelah laporan terkirim.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════ FORM CARD ═══════════════════ --}}
    <section class="mx-auto max-w-4xl fade-up delay-5">

        <form
            method="POST"
            action="{{ route('public.report.store') }}"
            class="rounded-2xl border border-slate-200 bg-white shadow-xl overflow-hidden"
            id="reportForm"
        >
            @csrf

            {{-- Form header --}}
            <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50 px-6 py-4">
                <div class="grid h-10 w-10 place-items-center rounded-xl bg-red-600 shadow">
                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-black text-slate-900">Formulir Laporan Darurat</p>
                    <p class="text-xs text-slate-500">Semua field wajib diisi sebelum mengirim laporan</p>
                </div>
            </div>

            <div class="p-6 space-y-6">

                {{-- ── Row 1: Nama & Nomor HP ── --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="reporter_name">
                            <svg class="inline mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                            Nama Pelapor
                        </label>
                        <input
                            id="reporter_name"
                            name="reporter_name"
                            type="text"
                            value="{{ old('reporter_name') }}"
                            placeholder="Masukkan nama lengkap…"
                            class="form-input"
                            required
                        >
                    </div>
                    <div>
                        <label class="form-label" for="reporter_phone">
                            <svg class="inline mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                            Nomor HP
                        </label>
                        <input
                            id="reporter_phone"
                            name="reporter_phone"
                            type="tel"
                            value="{{ old('reporter_phone') }}"
                            placeholder="08xxxxxxxxxx"
                            class="form-input"
                            required
                        >
                    </div>
                </div>

                {{-- ── Row 2: Jenis kejadian & Prioritas (custom dropdowns) ── --}}
                <div class="grid gap-4 sm:grid-cols-2">

                    {{-- Jenis Kejadian --}}
                    <div>
                        <label class="form-label">
                            <svg class="inline mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                            Jenis Kejadian
                        </label>
                        <div class="custom-select-wrapper" id="incidentWrapper">
                            <div class="select-trigger" id="incidentTrigger">
                                <span id="incidentIcon">⚡</span>
                                <span id="incidentLabel">Pilih jenis kejadian…</span>
                                <svg class="select-arrow h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                            </div>
                            <div class="select-dropdown" id="incidentDropdown">
                                <div class="select-option" data-value="Kecelakaan"  data-icon="🚗">🚗&nbsp; Kecelakaan</div>
                                <div class="select-option" data-value="Orang hilang" data-icon="🔍">🔍&nbsp; Orang Hilang</div>
                                <div class="select-option" data-value="Pendaki cedera" data-icon="🏔️">🏔️&nbsp; Pendaki Cedera</div>
                                <div class="select-option" data-value="Banjir" data-icon="🌊">🌊&nbsp; Banjir</div>
                                <div class="select-option" data-value="Kebakaran" data-icon="🔥">🔥&nbsp; Kebakaran</div>
                                <div class="select-option" data-value="Lainnya" data-icon="📋">📋&nbsp; Lainnya</div>
                            </div>
                            <input type="hidden" name="incident_type" id="incidentValue" value="{{ old('incident_type') }}" required>
                        </div>
                    </div>

                    {{-- Prioritas --}}
                    <div>
                        <label class="form-label">
                            <svg class="inline mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
                            Tingkat Prioritas
                        </label>
                        <div class="custom-select-wrapper" id="priorityWrapper">
                            <div class="select-trigger" id="priorityTrigger">
                                <span id="priorityIcon">⚡</span>
                                <span id="priorityLabel">Pilih prioritas…</span>
                                <svg class="select-arrow h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                            </div>
                            <div class="select-dropdown" id="priorityDropdown">
                                <div class="select-option" data-value="critical" data-icon="🚨">🚨&nbsp; Kritis — nyawa terancam</div>
                                <div class="select-option" data-value="high"     data-icon="🔴">🔴&nbsp; Tinggi — segera butuh bantuan</div>
                                <div class="select-option" data-value="medium"   data-icon="🟡">🟡&nbsp; Sedang — masih terkendali</div>
                                <div class="select-option" data-value="low"      data-icon="🟢">🟢&nbsp; Rendah — tidak mendesak</div>
                            </div>
                            <input type="hidden" name="priority" id="priorityValue" value="{{ old('priority', 'high') }}">
                        </div>
                    </div>

                </div>

                {{-- ── Deskripsi ── --}}
                <div>
                    <label class="form-label" for="description">
                        <svg class="inline mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/></svg>
                        Deskripsi Kejadian
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        placeholder="Jelaskan situasi darurat secara singkat — apa yang terjadi, berapa korban, kondisi saat ini…"
                        class="form-input resize-none"
                        required
                    >{{ old('description') }}</textarea>
                    <p class="mt-1.5 text-right text-xs text-slate-400" id="charCount">0 / 2000 karakter</p>
                </div>

                {{-- ── GPS / Lokasi ── --}}
                <div class="rounded-2xl border-2 border-slate-100 bg-slate-50 p-5 transition-all duration-300" id="locationSection">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <svg class="h-5 w-5 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                                <p class="font-bold text-slate-800">Lokasi Kejadian</p>
                                <span id="locationBadge" class="rounded-full px-3 py-0.5 text-xs font-bold bg-slate-200 text-slate-600">Belum aktif</span>
                            </div>
                            <p id="locationText" class="mt-1.5 text-sm text-slate-500 leading-relaxed">Tekan <strong>Aktifkan Lokasi</strong> di kanan, lalu izinkan akses GPS di browser.</p>
                        </div>
                        <button type="button" id="locateBtn" class="flex-shrink-0">
                            <span class="pulse-wrap" id="locatePulse" style="display:none">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                            </span>
                            <svg id="locateIcon" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                            <span id="locateBtnText">Aktifkan Lokasi</span>
                        </button>
                    </div>

                    <div id="locationHint" class="gps-status-card gps-idle mt-4 flex items-start gap-2">
                        <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                        <span id="locationHintText">Tips: gunakan Chrome/Safari, aktifkan lokasi presisi, dan tunggu beberapa detik sampai akurasi membaik.</span>
                    </div>

                    <div id="map" class="mt-4 h-72 rounded-xl border border-slate-200 overflow-hidden"></div>
                </div>

                {{-- Hidden GPS inputs --}}
                <input type="hidden" name="latitude"  id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <input type="hidden" name="accuracy"  id="accuracy">

                {{-- ── Submit ── --}}
                <button type="submit" id="submitBtn" class="disabled-state" disabled>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                    Aktifkan lokasi dulu untuk mengirim laporan
                </button>

            </div><!-- end p-6 -->
        </form>

        {{-- Disclaimer --}}
        <p class="mt-4 text-center text-xs text-slate-400 fade-up delay-5">
            Laporan ini akan langsung diteruskan ke posko TIMSAR. Harap hanya gunakan untuk keadaan darurat nyata.
        </p>
    </section>

    @push('scripts')
    <script>
    /* ═══════════════ MAP INIT ═══════════════ */
    const defaultPoint = [-8.5833, 116.1167];
    const map = L.map('map').setView(defaultPoint, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

    const markerIcon = L.divIcon({
        html: `<div style="width:28px;height:28px;border-radius:50% 50% 50% 0;background:#dc2626;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.35);transform:rotate(-45deg)"></div>`,
        iconSize: [28, 28],
        iconAnchor: [14, 28],
        className: ''
    });
    let marker = L.marker(defaultPoint, { icon: markerIcon }).addTo(map);

    let locationWatchId   = null;
    let bestPosition      = null;
    let watchStartedAt    = null;
    const targetAccuracy  = 50;
    const maxWatchMs      = 120000;

    /* ── refs ── */
    const locateBtn      = document.getElementById('locateBtn');
    const locateBtnText  = document.getElementById('locateBtnText');
    const locateIcon     = document.getElementById('locateIcon');
    const locatePulse    = document.getElementById('locatePulse');
    const submitBtn      = document.getElementById('submitBtn');
    const locationText   = document.getElementById('locationText');
    const locationBadge  = document.getElementById('locationBadge');
    const locationHint   = document.getElementById('locationHint');
    const locationHintTx = document.getElementById('locationHintText');
    const locationSection= document.getElementById('locationSection');

    /* ═══════════════ LOCATE BUTTON ═══════════════ */
    locateBtn.addEventListener('click', () => {
        if (!navigator.geolocation) {
            setGPSState('error', 'Browser tidak mendukung GPS.');
            return;
        }
        setLocateBtnState('loading');
        setGPSState('loading', 'Mencari GPS terbaik, tunggu beberapa detik…');
        setSubmitState(false);

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
                if (bestPosition) { applyPosition(bestPosition, true); return; }
                setGPSState('error', geolocationErrorMessage(err));
                setLocateBtnState('error');
            },
            { enableHighAccuracy: true, timeout: 30000, maximumAge: 0 }
        );
    });

    function applyPosition(pos, isFinal) {
        const { latitude: lat, longitude: lng, accuracy: acc } = pos.coords;
        document.getElementById('latitude').value  = lat;
        document.getElementById('longitude').value = lng;
        document.getElementById('accuracy').value  = acc;

        marker.setLatLng([lat, lng]);
        map.setView([lat, lng], acc <= 80 ? 17 : 15);
        locationSection.style.borderColor = isFinal ? '#86efac' : '#fde68a';

        if (isFinal) {
            setLocateBtnState('ready');
            setSubmitState(true);
            const state = acc > 100 ? 'warning' : 'ready';
            const msg = acc > 100
                ? `Lokasi aktif, akurasi sekitar ${Math.round(acc)} m. Laporan tetap bisa dikirim.`
                : `✓ Lokasi aktif! Akurasi ${Math.round(acc)} m — sangat baik.`;
            setGPSState(state, msg);
        } else {
            const waited = watchStartedAt ? Math.round((Date.now() - watchStartedAt) / 1000) : 0;
            setGPSState('loading', `Mengunci GPS… akurasi sementara ${Math.round(acc)} m (${waited}s)`);
        }
    }

    function setLocateBtnState(state) {
        locateBtn.className = '';
        locateBtn.disabled  = (state === 'loading');
        locatePulse.style.display = state === 'loading' ? 'inline-flex' : 'none';
        locateIcon.style.display  = state === 'loading' ? 'none' : 'inline';

        if (state === 'loading') {
            locateBtn.className = 'state-loading';
            locateBtnText.textContent = 'Mengunci GPS…';
        } else if (state === 'ready') {
            locateBtn.className = 'state-ready';
            locateBtnText.textContent = 'Perbarui Lokasi';
        } else if (state === 'error') {
            locateBtn.className = 'state-error';
            locateBtnText.textContent = 'Coba Lagi';
        } else {
            locateBtnText.textContent = 'Aktifkan Lokasi';
        }
    }

    function setGPSState(state, msg) {
        locationText.textContent = msg;
        const labels = {
            idle:    ['Belum aktif',   'bg-slate-200 text-slate-600'],
            loading: ['Mengunci GPS',  'bg-amber-100 text-amber-800'],
            ready:   ['Lokasi aktif',  'bg-emerald-100 text-emerald-700'],
            warning: ['Akurasi rendah','bg-yellow-100 text-yellow-800'],
            error:   ['Perlu izin',    'bg-red-100 text-red-700'],
        };
        const [label, cls] = labels[state] ?? labels.idle;
        locationBadge.textContent  = label;
        locationBadge.className    = `rounded-full px-3 py-0.5 text-xs font-bold ${cls}`;

        const hintClasses = {
            loading: 'gps-status-card gps-loading mt-4 flex items-start gap-2',
            ready:   'gps-status-card gps-ready mt-4 flex items-start gap-2',
            warning: 'gps-status-card gps-warning mt-4 flex items-start gap-2',
            error:   'gps-status-card gps-error mt-4 flex items-start gap-2',
        };
        locationHint.className = hintClasses[state] ?? 'gps-status-card gps-idle mt-4 flex items-start gap-2';
        locationHintTx.textContent = msg;
    }

    function setSubmitState(ready) {
        submitBtn.disabled = !ready;
        if (ready) {
            submitBtn.className = 'ready-state';
            submitBtn.innerHTML = `
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.769 59.769 0 0 1 3.269 20.875L5.999 12Zm0 0h7.5"/></svg>
                Kirim Laporan Darurat
            `;
        } else {
            submitBtn.className = 'disabled-state';
            submitBtn.innerHTML = `
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                Aktifkan lokasi dulu untuk mengirim laporan
            `;
        }
    }

    function geolocationErrorMessage(error) {
        if (error.code === error.PERMISSION_DENIED)   return 'Izin lokasi ditolak. Buka pengaturan browser → izinkan lokasi → coba lagi.';
        if (error.code === error.POSITION_UNAVAILABLE) return 'Lokasi tidak tersedia. Pastikan GPS aktif dan coba di area terbuka.';
        if (error.code === error.TIMEOUT)             return 'GPS terlalu lama merespons. Aktifkan presisi tinggi dan coba lagi.';
        return 'Gagal mengambil lokasi. Pastikan GPS dan izin browser aktif.';
    }

    /* ═══════════════ CUSTOM DROPDOWNS ═══════════════ */
    function initDropdown(wrapperId, triggerId, dropdownId, valueId, labelId, iconId) {
        const wrapper  = document.getElementById(wrapperId);
        const trigger  = document.getElementById(triggerId);
        const dropdown = document.getElementById(dropdownId);
        const hidInput = document.getElementById(valueId);
        const label    = document.getElementById(labelId);
        const icon     = document.getElementById(iconId);

        /* pre-select if old value exists */
        const oldVal = hidInput.value;
        if (oldVal) {
            const opt = dropdown.querySelector(`[data-value="${oldVal}"]`);
            if (opt) { label.textContent = opt.textContent.trim(); icon.textContent = opt.dataset.icon; opt.classList.add('active'); }
        }

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = wrapper.classList.contains('open');
            document.querySelectorAll('.custom-select-wrapper.open').forEach(w => w.classList.remove('open'));
            if (!isOpen) wrapper.classList.add('open');
        });

        dropdown.querySelectorAll('.select-option').forEach(opt => {
            opt.addEventListener('click', () => {
                hidInput.value = opt.dataset.value;
                label.textContent = opt.textContent.trim();
                icon.textContent  = opt.dataset.icon;
                dropdown.querySelectorAll('.select-option').forEach(o => o.classList.remove('active'));
                opt.classList.add('active');
                wrapper.classList.remove('open');
            });
        });
    }

    initDropdown('incidentWrapper','incidentTrigger','incidentDropdown','incidentValue','incidentLabel','incidentIcon');
    initDropdown('priorityWrapper','priorityTrigger','priorityDropdown','priorityValue','priorityLabel','priorityIcon');

    /* close on outside click */
    document.addEventListener('click', () => {
        document.querySelectorAll('.custom-select-wrapper.open').forEach(w => w.classList.remove('open'));
    });

    /* ═══════════════ CHAR COUNTER ═══════════════ */
    const descTa    = document.getElementById('description');
    const charCount = document.getElementById('charCount');
    descTa.addEventListener('input', () => {
        const n = descTa.value.length;
        charCount.textContent = `${n} / 2000 karakter`;
        charCount.style.color = n > 1800 ? '#ef4444' : n > 1500 ? '#f59e0b' : '#94a3b8';
    });

    /* ═══════════════ FORM SUBMIT LOADING ═══════════════ */
    document.getElementById('reportForm').addEventListener('submit', () => {
        submitBtn.innerHTML = `
            <div class="dot-loader" style="color:#fff">
                <span></span><span></span><span></span>
            </div>
            Mengirim laporan…
        `;
        submitBtn.disabled = true;
    });
    </script>
    @endpush
</x-layouts.app>
