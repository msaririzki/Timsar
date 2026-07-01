# IMPLEMENTASI PLAN APLIKASI TIMSAR
## Sistem Pelaporan Darurat dan Koordinasi TIMSAR Berbasis Mobile Computing

**Versi:** MVP 1 Hari  
**Platform:** Full Web Mobile-First / PWA  
**Target Mata Kuliah:** Mobile Computing  
**Tema:** TIMSAR — Tim Search and Rescue berbasis lokasi, jaringan bergerak, dan komputasi real-time

---

## 1. Gambaran Umum Sistem

Aplikasi **TIMSAR** adalah sistem pelaporan kejadian darurat berbasis web mobile-first. Sistem ini memungkinkan masyarakat mengirim laporan darurat lengkap dengan lokasi GPS, lalu laporan tersebut masuk ke dashboard admin dan dapat dilihat oleh anggota TIMSAR yang sedang aktif di lapangan.

Sistem ini dirancang agar tetap memenuhi kaidah **Mobile Computing**, meskipun berbasis full web. Pelapor dan anggota TIMSAR menggunakan perangkat bergerak seperti HP, berpindah lokasi, berpindah jaringan, dan tetap mengirim data lokasi ke server secara berkala.

### Konsep Utama

```text
Pelapor mengirim laporan + lokasi
↓
Admin menerima notifikasi laporan baru
↓
Sistem menghitung anggota/tim terdekat
↓
Admin menugaskan anggota atau regu TIMSAR
↓
Semua anggota TIMSAR mendapat informasi kejadian
↓
Anggota yang ditugaskan bergerak menuju lokasi
↓
Aplikasi menampilkan rute internal, jarak, dan estimasi tiba
↓
Pelapor dan admin dapat memantau status serta posisi petugas
↓
Kejadian ditangani dan laporan diselesaikan
```

---

## 2. Alasan Full Web

Karena waktu pengerjaan hanya sekitar **1 hari**, maka aplikasi dibuat dalam bentuk **full web responsive** agar lebih cepat dikembangkan dibandingkan membuat aplikasi mobile native atau Flutter.

Namun, sistem tetap dibuat seperti aplikasi mobile dengan pendekatan:

- Mobile-first responsive design
- Dapat dibuka dari HP
- Menggunakan GPS browser
- Menggunakan peta interaktif
- Mengirim lokasi berkala
- Menampilkan rute internal
- Mendukung banyak pengguna secara bersamaan
- Bisa dibuat menjadi PWA jika waktu memungkinkan

---

## 3. Kaidah Mobile Computing yang Dipenuhi

| Kaidah Mobile Computing | Implementasi pada TIMSAR |
|---|---|
| Mobility | Pelapor dan anggota TIMSAR menggunakan HP dan berpindah lokasi |
| Wireless Communication | Data dikirim melalui Wi-Fi atau jaringan seluler |
| Location Awareness | Sistem membaca GPS pelapor dan anggota TIMSAR |
| Context Awareness | Sistem mengetahui status laporan, posisi petugas, dan kondisi penugasan |
| Dynamic Computing | Sistem menghitung jarak, rute, estimasi waktu tiba, dan rekomendasi petugas |
| Real-Time Processing | Lokasi petugas dikirim dan diperbarui berkala |
| Network Handoff | Perangkat dapat berpindah dari Wi-Fi ke data seluler |
| Multi-user Access | Pelapor, admin, dan banyak anggota TIMSAR dapat mengakses sistem bersamaan |

---

## 4. Aktor dalam Sistem

### 4.1 Pelapor

Pelapor adalah masyarakat yang membuka web publik TIMSAR untuk mengirim laporan kejadian darurat.

Fitur pelapor:

- Mengisi nama
- Mengisi nomor HP
- Memilih jenis kejadian
- Mengisi deskripsi kejadian
- Mengaktifkan lokasi GPS
- Mengirim laporan
- Melihat halaman tracking laporan
- Melihat status laporan
- Melihat jarak dan estimasi petugas/tim yang menuju lokasi

### 4.2 Admin / Posko

Admin adalah pihak pusat atau posko TIMSAR yang memantau laporan masuk dan mengatur penugasan.

Fitur admin:

- Login dashboard
- Melihat laporan masuk secara real-time sederhana
- Melihat lokasi pelapor pada peta
- Melihat daftar anggota TIMSAR aktif
- Melihat anggota/tim terdekat
- Menugaskan anggota atau regu
- Memantau pergerakan anggota di peta
- Mengubah status laporan
- Menutup laporan setelah selesai

### 4.3 Anggota TIMSAR

Semua anggota TIMSAR memiliki akun masing-masing, bukan hanya ketua tim.

Alasan semua anggota punya akses:

- Jika ada anggota sedang berada di luar posko, dia langsung mengetahui kejadian.
- Admin bisa melihat anggota terdekat secara individual.
- Anggota yang paling dekat dapat segera merespons lebih awal.
- Setelah respons awal, posko tetap bisa mengirim regu tambahan.
- Sistem lebih kuat sebagai Mobile Computing karena banyak perangkat bergerak aktif.

Fitur anggota TIMSAR:

- Login dari HP
- Melihat daftar laporan aktif
- Menerima tugas dari admin
- Melihat detail kejadian
- Melihat lokasi pelapor
- Melihat rute internal di aplikasi
- Mengirim lokasi GPS berkala
- Mengubah status: menuju lokasi, sampai lokasi, menangani, selesai
- Melihat anggota lain yang satu regu jika diperlukan

---

## 5. Model Operasional TIMSAR

Sistem menggunakan kombinasi antara **respons individu terdekat** dan **regu TIMSAR**.

### 5.1 Respons Individu Terdekat

Jika ada anggota TIMSAR yang sedang berada dekat dengan lokasi kejadian, admin dapat menugaskan anggota tersebut sebagai **responder awal**.

Contoh:

```text
Laporan: Pendaki cedera
Lokasi: Bukit X
Anggota terdekat: Andi - 800 meter
Admin menugaskan Andi sebagai responder awal
```

### 5.2 Regu Utama

Setelah respon awal, admin dapat menugaskan satu regu utama dari posko atau dari daftar tim yang tersedia.

Contoh:

```text
Responder awal: Andi
Regu utama: TIMSAR 01
Status: Regu sedang menuju lokasi
```

### 5.3 Versi MVP 1 Hari

Untuk versi awal yang realistis dibuat dalam 1 hari:

- Semua anggota TIMSAR bisa login.
- Semua anggota bisa melihat laporan aktif.
- Admin dapat assign satu anggota atau satu regu.
- Tracking utama ditampilkan berdasarkan anggota yang ditugaskan.
- Fitur multi-regu bisa ditulis sebagai pengembangan lanjutan.

---

## 6. Teknologi yang Digunakan

| Kebutuhan | Teknologi |
|---|---|
| Backend | Laravel |
| Frontend | Blade |
| Styling | Tailwind CSS |
| Interaksi ringan | JavaScript / Alpine.js |
| Database | MySQL |
| Peta | Leaflet |
| Tile Map | OpenStreetMap |
| GPS | Browser Geolocation API |
| Rute internal | OSRM Route API |
| Real-time sederhana | AJAX polling |
| Auth | Laravel Auth / session login |
| Deploy demo | Cloudflare Tunnel atau server lokal HTTPS |
| Optional | PWA manifest untuk tampilan seperti aplikasi |

---

## 7. Konsep UI Modern

UI harus terlihat modern, bersih, dan nyaman digunakan di HP maupun laptop.

### 7.1 Gaya Desain

Konsep visual:

```text
Modern emergency response dashboard
Clean
Mobile-first
Card-based
Map-focused
Fast action button
Soft shadow
Rounded corner
Clear status color
```

### 7.2 Warna Utama

| Fungsi | Warna |
|---|---|
| Primary | Biru gelap / navy |
| Emergency | Merah |
| Success | Hijau |
| Warning | Kuning / amber |
| Info | Biru |
| Background | Abu muda / slate-50 |
| Card | Putih |
| Text utama | Slate-900 |
| Text sekunder | Slate-500 |

Contoh palet:

```text
Primary: #0F172A
Emergency: #EF4444
Success: #22C55E
Warning: #F59E0B
Info: #3B82F6
Background: #F8FAFC
Card: #FFFFFF
```

### 7.3 Komponen UI

Komponen utama:

- Navbar sederhana
- Bottom navigation untuk anggota TIMSAR di HP
- Card laporan
- Status badge
- Map card
- Floating action button
- Modal detail laporan
- Toast notification
- Timeline status laporan
- Skeleton loading
- Empty state
- Alert emergency

### 7.4 Desain Halaman Pelapor

Halaman pelapor dibuat sederhana dan fokus.

Komponen:

```text
Header:
TIMSAR Emergency Report

Card form:
- Nama pelapor
- Nomor HP
- Jenis kejadian
- Deskripsi
- Tombol Aktifkan Lokasi
- Preview peta lokasi
- Tombol Kirim Laporan

Footer:
Nomor darurat / informasi singkat
```

Karakter UI:

- Tombol utama merah
- Form besar dan mudah diklik
- Map preview jelas
- Indikator lokasi berhasil didapat

### 7.5 Desain Halaman Tracking Pelapor

Setelah laporan dikirim, pelapor diarahkan ke halaman tracking.

Komponen:

```text
Status card:
- Laporan diterima
- Menunggu admin
- Tim menuju lokasi
- Tim sampai lokasi
- Selesai

Map:
- Marker lokasi pelapor
- Marker anggota/tim yang ditugaskan
- Rute menuju lokasi

Info card:
- Nama petugas/tim
- Jarak
- Estimasi tiba
- Update terakhir
```

### 7.6 Desain Dashboard Admin

Dashboard admin dibuat lebih informatif.

Layout desktop:

```text
Sidebar kiri:
- Dashboard
- Laporan
- Anggota TIMSAR
- Regu
- Riwayat

Topbar:
- Search
- Notifikasi
- Profil admin

Main:
- Statistik laporan
- Peta besar
- Tabel laporan aktif
- Panel anggota terdekat
```

Komponen statistik:

```text
Laporan baru
Sedang ditangani
Tim aktif
Selesai hari ini
```

Peta admin menampilkan:

- Marker laporan baru
- Marker anggota TIMSAR
- Marker regu
- Warna marker sesuai status

### 7.7 Desain Halaman Anggota TIMSAR

Halaman anggota harus nyaman di HP.

Layout mobile:

```text
Top status:
- Nama anggota
- Status online/offline
- Tombol aktifkan lokasi

Card tugas:
- Jenis kejadian
- Lokasi
- Status
- Jarak
- Estimasi

Map:
- Posisi anggota
- Lokasi pelapor
- Rute internal

Action button:
- Terima tugas
- Mulai menuju lokasi
- Sampai lokasi
- Selesai
```

Bottom navigation:

```text
Beranda | Laporan | Peta | Profil
```

---

## 8. Routing Internal dalam Aplikasi

Aplikasi tidak diarahkan ke Google Maps. Semua navigasi dasar tetap ditampilkan di aplikasi TIMSAR.

### 8.1 Cara Kerja Rute

1. Sistem mengambil lokasi anggota TIMSAR.
2. Sistem mengambil lokasi pelapor.
3. Backend atau frontend meminta rute ke OSRM.
4. OSRM mengembalikan jarak, durasi, dan geometry rute.
5. Leaflet menggambar rute di peta aplikasi.
6. Sistem menampilkan estimasi waktu tiba.

### 8.2 Data Rute yang Ditampilkan

Pada halaman anggota:

```text
Jarak rute: 3.2 km
Estimasi tiba: ± 9 menit
Status: Menuju lokasi
```

Pada halaman pelapor:

```text
Petugas menuju lokasi Anda
Jarak petugas: 3.2 km
Estimasi tiba: ± 9 menit
Update terakhir: 21:45
```

### 8.3 Fallback Jika Routing Gagal

Jika OSRM gagal, sistem tetap menampilkan:

- Marker petugas
- Marker pelapor
- Garis lurus
- Jarak Haversine
- Estimasi kasar

Pesan:

```text
Rute jalan belum tersedia. Sistem menampilkan estimasi berdasarkan jarak garis lurus.
```

---

## 9. Database Design

### 9.1 Tabel `users`

Untuk admin dan anggota TIMSAR.

```text
id
name
email
password
role
phone
created_at
updated_at
```

Role:

```text
admin
member
```

### 9.2 Tabel `teams`

Untuk regu TIMSAR.

```text
id
team_code
team_name
leader_id
vehicle_type
member_count
status
created_at
updated_at
```

Status:

```text
available
assigned
on_the_way
handling
offline
```

### 9.3 Tabel `team_members`

Untuk relasi anggota dengan tim.

```text
id
team_id
user_id
position
is_leader
created_at
updated_at
```

Contoh position:

```text
ketua
driver
medis
navigator
anggota
```

### 9.4 Tabel `member_locations`

Untuk posisi terakhir setiap anggota TIMSAR.

```text
id
user_id
latitude
longitude
accuracy
speed
network_type
is_online
last_seen_at
created_at
updated_at
```

### 9.5 Tabel `reports`

Untuk laporan dari masyarakat.

```text
id
tracking_code
reporter_name
reporter_phone
incident_type
description
latitude
longitude
accuracy
status
priority
assigned_member_id
assigned_team_id
created_at
updated_at
```

Status:

```text
new
verified
assigned
on_the_way
arrived
handling
completed
cancelled
```

Priority:

```text
low
medium
high
critical
```

### 9.6 Tabel `assignments`

Untuk penugasan laporan.

```text
id
report_id
assigned_member_id
assigned_team_id
assigned_by
assignment_type
status
assigned_at
accepted_at
started_at
arrived_at
completed_at
distance_meters
duration_seconds
route_geometry_json
route_steps_json
created_at
updated_at
```

Assignment type:

```text
individual
team
```

Status:

```text
assigned
accepted
on_the_way
arrived
handling
completed
cancelled
```

### 9.7 Tabel `location_logs`

Untuk riwayat lokasi anggota selama bergerak.

```text
id
user_id
assignment_id
latitude
longitude
accuracy
speed
network_type
recorded_at
created_at
```

### 9.8 Tabel `notifications`

Untuk notifikasi internal.

```text
id
user_id
report_id
title
message
type
is_read
created_at
updated_at
```

---

## 10. Route Laravel

### 10.1 Public

```text
GET  /lapor
POST /lapor
GET  /lacak/{tracking_code}
GET  /api/public/tracking/{tracking_code}
```

### 10.2 Auth

```text
GET  /login
POST /login
POST /logout
```

### 10.3 Admin

```text
GET  /admin/dashboard
GET  /admin/reports
GET  /admin/reports/{report}
POST /admin/reports/{report}/verify
POST /admin/reports/{report}/assign-member
POST /admin/reports/{report}/assign-team
POST /admin/reports/{report}/cancel

GET  /admin/api/reports
GET  /admin/api/map-data
GET  /admin/api/nearest-members/{report}
GET  /admin/api/nearest-teams/{report}
```

### 10.4 Anggota TIMSAR

```text
GET  /member/dashboard
GET  /member/reports
GET  /member/assignments/{assignment}
POST /member/assignments/{assignment}/accept
POST /member/assignments/{assignment}/start
POST /member/assignments/{assignment}/arrive
POST /member/assignments/{assignment}/complete
POST /member/location/update

GET  /member/api/active-assignment
GET  /member/api/reports
GET  /member/api/route/{assignment}
```

### 10.5 Routing

```text
GET /api/route
```

Parameter:

```text
from_lat
from_lng
to_lat
to_lng
```

Response:

```json
{
  "success": true,
  "distance_meters": 3200,
  "duration_seconds": 540,
  "geometry": {},
  "steps": []
}
```

---

## 11. Service Laravel yang Dibutuhkan

### 11.1 `DistanceService`

Fungsi:

- Menghitung jarak Haversine
- Mengurutkan anggota terdekat
- Mengurutkan regu terdekat

### 11.2 `RoutingService`

Fungsi:

- Mengambil rute dari OSRM
- Mengambil jarak rute
- Mengambil durasi estimasi
- Mengambil geometry rute
- Mengambil instruksi langkah
- Menyediakan fallback jika OSRM gagal

### 11.3 `AssignmentService`

Fungsi:

- Assign anggota
- Assign regu
- Mengubah status laporan
- Mengubah status petugas
- Membuat data assignment

### 11.4 `TrackingService`

Fungsi:

- Update lokasi anggota
- Simpan location log
- Update posisi terakhir
- Hitung ulang jarak ke lokasi kejadian
- Update estimasi tiba

### 11.5 `NotificationService`

Fungsi:

- Membuat notifikasi laporan baru
- Membuat notifikasi tugas baru untuk anggota
- Membuat notifikasi status laporan berubah

---

## 12. Alur Detail Sistem

### 12.1 Pelapor Membuat Laporan

```text
1. Pelapor membuka /lapor
2. Pelapor mengisi data diri dan kejadian
3. Pelapor menekan tombol Aktifkan Lokasi
4. Browser meminta izin lokasi
5. Sistem menampilkan preview peta
6. Pelapor menekan Kirim Laporan
7. Sistem menyimpan laporan ke database
8. Sistem membuat tracking_code
9. Pelapor diarahkan ke halaman /lacak/{tracking_code}
```

### 12.2 Admin Menangani Laporan

```text
1. Admin membuka dashboard
2. Dashboard melakukan polling laporan baru
3. Laporan baru muncul dengan badge emergency
4. Admin membuka detail laporan
5. Sistem menampilkan peta lokasi kejadian
6. Sistem menampilkan anggota terdekat
7. Sistem menampilkan regu terdekat
8. Admin memilih assign anggota atau regu
9. Sistem mengubah status laporan menjadi assigned
10. Anggota mendapat notifikasi tugas
```

### 12.3 Anggota TIMSAR Bertindak

```text
1. Anggota login dari HP
2. Anggota melihat laporan aktif atau tugas yang diberikan
3. Anggota menerima tugas
4. Aplikasi mengambil lokasi anggota
5. Aplikasi menghitung rute ke lokasi pelapor
6. Rute ditampilkan di peta aplikasi
7. Anggota menekan Mulai Menuju Lokasi
8. Lokasi anggota dikirim berkala
9. Admin dan pelapor melihat update posisi
10. Anggota menekan Sampai Lokasi
11. Anggota menekan Selesai setelah kejadian ditangani
```

---

## 13. Real-Time Sederhana

Karena waktu pengerjaan singkat, sistem menggunakan polling AJAX.

### Interval yang Disarankan

| Halaman | Data | Interval |
|---|---|---|
| Admin dashboard | Laporan baru | 3 detik |
| Admin map | Posisi anggota | 5 detik |
| Pelapor tracking | Status dan posisi petugas | 5 detik |
| Member dashboard | Tugas baru | 5 detik |
| Member location sender | Kirim GPS | 5–10 detik |

---

## 14. Deteksi Jaringan

Browser dapat membaca status online/offline dengan:

```javascript
navigator.onLine
```

Jika tersedia, bisa membaca jenis koneksi dengan:

```javascript
navigator.connection?.effectiveType
navigator.connection?.type
```

Data yang disimpan:

```text
wifi
cellular
4g
3g
unknown
offline
```

Jika browser tidak mendukung deteksi jenis jaringan, sistem tetap menyimpan:

```text
unknown
```

Yang penting untuk demo:

```text
Status koneksi: Online
Update terakhir: 21:45:10
Jenis jaringan: 4g / wifi / unknown
```

---

## 15. Offline Handling Sederhana

Jika lokasi gagal dikirim karena jaringan putus:

1. Simpan data lokasi sementara di `localStorage`.
2. Tampilkan status “Menunggu koneksi”.
3. Saat online kembali, kirim ulang lokasi yang tertunda.
4. Update status menjadi online.

Fitur ini opsional jika waktu mencukupi.

---

## 16. Timeline Pengerjaan 1 Hari

### Jam 1–2: Setup Project

Target:

- Install Laravel
- Setup database
- Setup Tailwind
- Buat auth sederhana
- Buat layout utama

Checklist:

```text
Laravel jalan
Database terkoneksi
Login admin/member bisa
Tailwind aktif
```

### Jam 3–4: Modul Pelapor

Target:

- Halaman form laporan
- Ambil lokasi GPS
- Preview peta Leaflet
- Simpan laporan
- Halaman tracking awal

Checklist:

```text
Pelapor bisa kirim laporan
Latitude longitude tersimpan
Tracking code terbentuk
```

### Jam 5–6: Modul Admin

Target:

- Dashboard admin
- Tabel laporan
- Detail laporan
- Peta laporan
- Polling laporan baru
- Data anggota terdekat

Checklist:

```text
Admin bisa melihat laporan
Admin bisa melihat lokasi pelapor
Admin bisa assign anggota/regu
```

### Jam 7–8: Modul Anggota TIMSAR

Target:

- Dashboard anggota
- Daftar laporan aktif
- Detail tugas
- Update lokasi GPS
- Tombol accept/start/arrive/complete

Checklist:

```text
Anggota bisa login
Anggota bisa menerima tugas
Lokasi anggota bisa dikirim ke server
```

### Jam 9–10: Routing Internal

Target:

- Integrasi OSRM
- Gambar rute di Leaflet
- Hitung jarak
- Hitung estimasi
- Tampilkan rute di member dan tracking pelapor

Checklist:

```text
Rute muncul di peta
Jarak tampil
Estimasi tampil
Fallback garis lurus tersedia
```

### Jam 11–12: UI Polish dan Demo

Target:

- Rapikan UI modern
- Tambahkan badge status
- Tambahkan warna emergency
- Tambahkan loading state
- Tambahkan audio/notifikasi sederhana
- Siapkan data dummy anggota/regu
- Siapkan skenario demo

Checklist:

```text
UI nyaman dilihat
Demo berjalan dari pelapor → admin → anggota → tracking
```

---

## 17. Prioritas Fitur

### Wajib

```text
Form laporan
GPS pelapor
Dashboard admin
Login anggota
GPS anggota
Assign anggota/regu
Peta Leaflet
Rute internal OSRM
Tracking pelapor
Jarak dan estimasi
Status laporan
```

### Opsional

```text
PWA installable
Offline localStorage
Audio alert
Upload foto
Notifikasi internal
Riwayat lokasi lengkap
Multi-regu
```

### Ditunda

```text
Chat
Video call
Firebase push notification
WebSocket
AI prioritas kejadian
Voice navigation
Aplikasi Flutter
```

---

## 18. Struktur Folder Laravel

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── PublicReportController.php
│   │   ├── PublicTrackingController.php
│   │   ├── AdminDashboardController.php
│   │   ├── AdminReportController.php
│   │   ├── MemberDashboardController.php
│   │   ├── MemberAssignmentController.php
│   │   ├── LocationController.php
│   │   └── RouteController.php
│   └── Middleware/
├── Models/
│   ├── Report.php
│   ├── Team.php
│   ├── TeamMember.php
│   ├── MemberLocation.php
│   ├── Assignment.php
│   ├── LocationLog.php
│   └── Notification.php
├── Services/
│   ├── DistanceService.php
│   ├── RoutingService.php
│   ├── AssignmentService.php
│   ├── TrackingService.php
│   └── NotificationService.php

resources/views/
├── layouts/
│   ├── app.blade.php
│   ├── admin.blade.php
│   └── member.blade.php
├── public/
│   ├── report.blade.php
│   └── tracking.blade.php
├── admin/
│   ├── dashboard.blade.php
│   ├── reports.blade.php
│   └── report-detail.blade.php
├── member/
│   ├── dashboard.blade.php
│   ├── reports.blade.php
│   └── assignment-detail.blade.php
```

---

## 19. Skenario Demo

### Perangkat

```text
Laptop: Admin dashboard
HP 1: Pelapor
HP 2: Anggota TIMSAR
HP 3 opsional: Anggota TIMSAR lain
```

### Alur Demo

```text
1. Pelapor membuka halaman /lapor
2. Pelapor mengaktifkan lokasi
3. Pelapor mengirim laporan
4. Admin melihat notifikasi laporan baru
5. Admin membuka detail laporan
6. Admin melihat anggota terdekat
7. Admin assign anggota TIMSAR
8. Anggota menerima tugas
9. Anggota membuka rute internal
10. Anggota klik Mulai Menuju Lokasi
11. Lokasi anggota bergerak dan tampil di admin
12. Pelapor melihat jarak dan estimasi tiba
13. Anggota berpindah jaringan Wi-Fi ke data seluler
14. Sistem tetap update lokasi
15. Anggota klik Sampai Lokasi
16. Anggota klik Selesai
17. Laporan berubah menjadi completed
```

---

## 20. Narasi Presentasi

Narasi singkat:

> Aplikasi TIMSAR adalah sistem pelaporan darurat berbasis mobile web yang menerapkan konsep Mobile Computing. Pelapor dan anggota TIMSAR menggunakan perangkat bergerak untuk mengirim lokasi GPS. Sistem melakukan komputasi jarak, rute, estimasi waktu tiba, dan rekomendasi anggota terdekat secara dinamis. Data lokasi diperbarui secara berkala sehingga admin dan pelapor dapat memantau pergerakan petugas secara real-time. Sistem tetap berjalan saat perangkat berpindah jaringan dari Wi-Fi ke data seluler, sehingga memenuhi konsep mobility, wireless communication, location awareness, context awareness, dan dynamic computing.

---

## 21. Kesimpulan Implementasi

Versi terbaik untuk waktu 1 hari adalah:

```text
Full web mobile-first
Laravel monolith
UI modern berbasis Tailwind
Leaflet untuk peta
OSRM untuk rute internal
GPS browser untuk lokasi
Polling untuk real-time sederhana
Semua anggota TIMSAR punya akses sendiri
Admin bisa assign anggota atau regu
Pelapor bisa melihat status, jarak, dan estimasi
```

Dengan desain ini, aplikasi tetap realistis untuk dibuat cepat, tampilan bisa modern, dan konsep Mobile Computing dapat dijelaskan dengan kuat.
