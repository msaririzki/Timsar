<x-layouts.app title="Login TIMSAR">

    @push('scripts')
        <style>
            /* ── Animations ── */
            @keyframes fadeUp {
                from { opacity: 0; transform: translateY(12px); }
                to   { opacity: 1; transform: translateY(0); }
            }
            .fade-up { animation: fadeUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) both; }

            /* ── Form Inputs ── */
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
                transition: color 0.15s ease;
            }
            .form-input {
                width: 100%;
                padding: 0.625rem 0.75rem 0.625rem 2.25rem;
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

            /* ── Demo Buttons ── */
            .demo-btn {
                cursor: pointer;
                transition: all 0.15s ease;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
            }
            .demo-btn:hover {
                border-color: #dc2626;
                color: #dc2626;
                background: #fef2f2;
            }
        </style>
    @endpush

    <div class="mx-auto max-w-md px-2 sm:px-0 py-8 sm:py-12 fade-up">
        
        <form 
            method="POST" 
            action="{{ route('login.store') }}" 
            class="rounded-xl border border-slate-200 bg-white p-6 shadow-md"
            id="loginForm"
        >
            @csrf
            
            {{-- Header --}}
            <div class="text-center mb-6">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-red-600 font-black text-white text-sm shadow-sm mb-2.5">TS</span>
                <h1 class="text-lg font-extrabold text-slate-900">Masuk Posko TIMSAR</h1>
                <p class="text-xs text-slate-400 mt-0.5">Khusus admin dan anggota lapangan</p>
            </div>

            <div class="space-y-4">
                {{-- Email --}}
                <div>
                    <label class="form-label" for="email">Email</label>
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
                    <label class="form-label" for="password">Password</label>
                    <div class="form-input-group">
                        <input 
                            id="password"
                            name="password" 
                            type="password"
                            value="password" 
                            placeholder="Password Anda…"
                            class="form-input" 
                            required
                        >
                        <svg class="form-input-icon h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                    </div>
                </div>

                {{-- Submit button --}}
                <button type="submit" id="loginBtn" class="w-full rounded-lg bg-red-600 py-2.5 text-sm font-bold text-white shadow hover:bg-red-700 active:translate-y-px transition-all">
                    Masuk
                </button>
            </div>

            {{-- Demo Auto-Fill (Super clean & minimal) --}}
            <div class="mt-6 pt-4 border-t border-slate-100">
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 text-center mb-2">Gunakan Akun Demo</p>
                <div class="grid grid-cols-2 gap-2">
                    <button 
                        type="button" 
                        class="demo-btn rounded px-2 py-1.5 text-center text-xs font-bold"
                        onclick="fillCredentials('admin@timsar.test', 'password')"
                    >
                        🔑 Admin
                    </button>
                    <button 
                        type="button" 
                        class="demo-btn rounded px-2 py-1.5 text-center text-xs font-bold"
                        onclick="fillCredentials('andi@timsar.test', 'password')"
                    >
                        🔑 Anggota
                    </button>
                </div>
            </div>

        </form>
    </div>

    @push('scripts')
    <script>
        function fillCredentials(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
            document.getElementById('email').focus();
        }

        document.getElementById('loginForm').addEventListener('submit', () => {
            const btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.innerHTML = 'Memproses masuk…';
            btn.className = 'w-full rounded-lg bg-slate-400 py-2.5 text-sm font-bold text-white cursor-not-allowed';
        });
    </script>
    @endpush
</x-layouts.app>
