<x-layouts.app title="Login TIMSAR">
    <section class="mx-auto grid max-w-5xl gap-6 md:grid-cols-[1fr_420px] md:items-center">
        <div class="rounded-2xl bg-slate-900 p-8 text-white">
            <p class="text-sm font-bold uppercase text-red-300">Akses internal</p>
            <h1 class="mt-3 text-3xl font-black leading-tight md:text-5xl">Posko dan anggota TIMSAR</h1>
            <p class="mt-4 max-w-xl text-slate-300">Login digunakan oleh admin posko dan anggota lapangan. Masyarakat tidak perlu login untuk mengirim laporan.</p>
            <div class="mt-6 grid gap-3 text-sm font-semibold text-slate-200 sm:grid-cols-2">
                <div class="rounded-xl bg-white/10 p-4">Admin memantau laporan, peta, dan anggota terdekat.</div>
                <div class="rounded-xl bg-white/10 p-4">Anggota mengirim GPS dan status jaringan tiap 5 detik.</div>
            </div>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <h2 class="text-2xl font-black">Masuk</h2>
            <p class="mt-1 text-sm text-slate-500">Gunakan akun demo dari seeder.</p>
            <label class="mt-5 block text-sm font-bold">Email</label>
            <input name="email" value="{{ old('email', 'admin@timsar.test') }}" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-red-500 focus:outline-none" type="email" required>
            <label class="mt-4 block text-sm font-bold">Password</label>
            <input name="password" value="password" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-red-500 focus:outline-none" type="password" required>
            <button class="mt-6 w-full rounded-xl bg-red-600 px-4 py-3 font-black text-white shadow-sm hover:bg-red-700">Login</button>
            <div class="mt-5 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">
                <p><strong>Admin:</strong> admin@timsar.test / password</p>
                <p><strong>Anggota:</strong> andi@timsar.test / password</p>
            </div>
        </form>
    </section>
</x-layouts.app>
