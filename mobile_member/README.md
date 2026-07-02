# TIMSAR Anggota Android

Aplikasi Android anggota TIMSAR. Aplikasi membuka web produksi dalam WebView,
membaca serving cell Android melalui `TelephonyManager`, lalu meneruskan data
BTS ke halaman anggota setiap 5 detik.

## Menjalankan

```bash
flutter pub get
flutter run
```

Gunakan HP Android fisik dengan SIM aktif. Aktifkan GPS, beri izin lokasi
presisi, dan gunakan halaman tugas anggota agar observasi BTS tersimpan bersama
titik GPS.

## Getting Started

This project is a starting point for a Flutter application.

A few resources to get you started if this is your first Flutter project:

- [Lab: Write your first Flutter app](https://docs.flutter.dev/get-started/codelab)
- [Cookbook: Useful Flutter samples](https://docs.flutter.dev/cookbook)

For help getting started with Flutter development, view the
[online documentation](https://docs.flutter.dev/), which offers tutorials,
samples, guidance on mobile development, and a full API reference.
