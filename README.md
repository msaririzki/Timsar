# Timsar

Aplikasi pelaporan darurat dan koordinasi anggota TIMSAR berbasis Laravel, Blade, Leaflet, dan OSRM.

## Fitur MVP

- Form laporan publik tanpa login.
- GPS pelapor dari browser.
- Login admin posko dan anggota TIMSAR.
- Dashboard admin dengan peta laporan dan anggota aktif.
- Assign anggota ke laporan.
- Dashboard anggota mobile-first.
- Pengiriman lokasi anggota tiap 5 detik.
- Pencatatan jenis jaringan anggota.
- Perhitungan jarak, rute, dan estimasi tiba di server.
- Tracking publik dengan kode laporan.
- Aplikasi Android anggota dengan pembacaan serving cell LTE/NR/WCDMA/GSM.
- Pencatatan observasi BTS dan event handover pada koordinat GPS petugas.

## Akun Demo

```text
Admin:
admin@timsar.test / password

Anggota:
andi@timsar.test / password
budi@timsar.test / password
sari@timsar.test / password
raka@timsar.test / password
nadia@timsar.test / password
```

## Menjalankan Lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve --host=0.0.0.0 --port=8000
```

Untuk demo HP, gunakan HTTPS melalui Cloudflare Tunnel atau reverse proxy ke port `8000`.

## APK Anggota

Kode Flutter berada di `mobile_member`. Build APK pengujian dengan:

```bash
cd mobile_member
flutter pub get
flutter build apk --release
```

APK membutuhkan HP Android bersim aktif dan izin lokasi presisi. Identitas BTS
hanya dikirim ketika halaman anggota terbuka dan update GPS berjalan.
