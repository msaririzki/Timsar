<x-layouts.app title="Login TIMSAR">

    @push('scripts')
        <style>
            /* ── Animations ── */
            @keyframes fadeUp {
                from { opacity: 0; transform: translateY(12px); }
                to   { opacity: 1; transform: translateY(0); }
            }
            .fade-up { animation: fadeUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) both; }
            .delay-1 { animation-delay: .05s; }
            .delay-2 { animation-delay: .1s; }

            /* ── Modern Form Inputs ── */
            .form-input-group {
                position: relative;
            }
            .form-input-icon {
                position: absolute;
                left: 0.875rem;
                top: 50%;
                transform: translateY(-50%);
                color: #94a3b8;
                pointer-events: none;
                transition: color 0.2s ease;
            }
            .form-input {
                width: 100%;
                padding: 0.75rem 0.875rem 0.75rem 2.25rem;
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
            .form-input:focus + .form-input-icon {
                color: #dc2626;
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

            /* ── Interactive Credential Cards ── */
            .credential-pill {
                cursor: pointer;
                transition: all 0.2s ease;
                border: 1px dashed #cbd5e1;
            }
            .credential-pill:hover {
                border-color: #dc2626;
                background-color: #fef2f2;
                transform: translateY(-1px);
            }
            .credential-pill:active {
                transform: translateY(0);
            }
        </style>
    @endpush

    <section class="mx-auto max-w-4xl px-2 sm:px-0 py-4 sm:py-8 fade-up">
        <div class="grid gap-6 md:grid-cols-[1fr_400px] md:items-stretch">

            {{-- ── LEFT PANEL: INFO CARD ── --}}
            <div class="rounded-xl bg-slate-900 p-6 sm:p-8 text-white flex flex-col justify-between shadow-lg relative overflow-hidden">
                {{-- Decorative background gradient --}}
                <div class="absolute -right-16 -top-16 w-48 h-48 rounded-full bg-red-600/10 blur-3xl pointer-events-none"></div>
                <div class="absolute -left-16 -bottom-16 w-48 h-48 rounded-full bg-blue-600/10 blur-3xl pointer-events-none"></div>

                <div class="relative z-10">
                    <span class="inline-flex items-center gap-1 rounded bg-red-500/20 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-red-300 border border-red-500/30">
                        Akses Internal
                    </span>
                    <h1 class="mt-4 text-2xl sm:text-3.5xl font-black leading-tight tracking-tight">
                        Posko & Anggota Lapangan
                    </h1>
                    <p class="mt-3 text-xs sm:text-sm text-slate-300 leading-relaxed max-w-lg">
                        Halaman masuk khusus untuk administrator posko TIMSAR dan anggota yang bertugas di lapangan. Masyarakat umum dapat mengirim laporan langsung tanpa akun.
                    </p>
                </div>

                <div class="mt-8 grid gap-3 text-xs text-slate-300 sm:grid-cols-2 relative z-10">
                    <div class="rounded-lg bg-white/5 border border-white/10 p-3.5">
                        <p class="font-bold text-white flex items-center gap-1.5 mb-1 text-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                            Posko & Admin
                        </p>
                        <p class="text-slate-400 text-[11px] leading-relaxed">Kelola peta kejadian, koordinasi anggota, dan penugasan langsung.</p>
                    </div>
                    <div class="rounded-lg bg-white/5 border border-white/10 p-3.5">
                        <p class="font-bold text-white flex items-center gap-1.5 mb-1 text-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                            Anggota Lapangan
                        </p>
                        <p class="text-slate-400 text-[11px] leading-relaxed">Update posisi GPS secara real-time dan terima penugasan darurat.</p>
                    </div>
                </div>
            </div>

            {{-- ── RIGHT PANEL: LOGIN FORM ── --}}
            <form
                method="POST"
                action="{{ route('login.store') }}"
                class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6 shadow-md flex flex-col justify-between fade-up delay-1"
                id="loginForm"
            >
                @csrf
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-slate-900">Masuk Akun</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Silakan masukkan email dan kata sandi posko Anda.</p>

                    <div class="mt-5 space-y-4">
                        {{-- Email --}}
                        <div>
                            <label class="form-label" for="email">Alamat Email</label>
                            <div class="form-input-group">
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value="{{ old('email', 'admin@timsar.test') }}"
                                    placeholder="nama@timsar.test"
                                    class="form-input"
                                    required
                                >
                                <svg class="form-input-icon h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                </svg>
                            </div>
                        </div>

                        {{-- Password --}}
                        <div>
                            <label class="form-label" for="password">Kata Sandi</label>
                            <div class="form-input-group">
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    value="password"
                                    placeholder="••••••••"
                                    class="form-input"
                                    required
                                >
                                <svg class="form-input-icon h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    {{-- Submit button --}}
                    <button type="submit" id="loginBtn" class="mt-5 w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-bold text-white shadow hover:bg-red-700 active:translate-y-px transition-all">
                        Masuk Sekarang
                    </button>

                    {{-- Clickable Demo Accounts Card --}}
                    <div class="mt-5 rounded-lg border border-slate-100 bg-slate-50/70 p-3.5">
                        <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">Akun Demo (Klik untuk auto-fill)</p>
                        <div class="grid gap-2 text-xs">
                            <div class="credential-pill rounded-md bg-white p-2 flex items-center justify-between" onclick="fillCredentials('admin@timsar.test', 'password')">
                                <div>
                                    <span class="font-semibold text-slate-800 text-[11px]">Posko Admin</span>
                                    <p class="text-[10px] text-slate-400 font-mono">admin@timsar.test</p>
                                </div>
                                <span class="text-[10px] bg-red-50 text-red-600 px-1.5 py-0.5 rounded font-bold">Pilih</span>
                            </div>
                            <div class="credential-pill rounded-md bg-white p-2 flex items-center justify-between" onclick="fillCredentials('andi@timsar.test', 'password')">
                                <div>
                                    <span class="font-semibold text-slate-800 text-[11px]">Anggota Lapangan</span>
                                    <p class="text-[10px] text-slate-400 font-mono">andi@timsar.test</p>
                                </div>
                                <span class="text-[10px] bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded font-bold">Pilih</span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </section>

    @push('scripts')
    <script>
        function fillCredentials(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;

            // Trigger visual focus effect
            const emailInput = document.getElementById('email');
            emailInput.classList.add('is-valid');
            setTimeout(() => {
                emailInput.focus();
            }, 100);
        }

        document.getElementById('loginForm').addEventListener('submit', () => {
            const btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.innerHTML = 'Memproses masuk…';
            btn.className = 'mt-5 w-full rounded-lg bg-slate-400 px-4 py-2.5 text-sm font-bold text-white cursor-not-allowed';
        });
    </script>
    @endpush
</x-layouts.app>
