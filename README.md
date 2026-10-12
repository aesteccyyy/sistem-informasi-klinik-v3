# Nusahomelab Clinic

Aplikasi operasional klinik untuk mengelola pasien, pegawai, appointment, pemeriksaan, resep, tindakan, stok obat, billing, dan pembayaran.

## Menjalankan secara lokal

Persyaratan: PHP 8.2+, Composer, Node.js/npm, dan MySQL.

```sh
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Isi koneksi database pada `.env`, buat database kosong, lalu jalankan:

```sh
php artisan migrate
php artisan storage:link
php artisan serve
npm run dev
```

Panduan menelusuri error berdasarkan halaman dan file kode tersedia di [docs/panduan-pemecahan-masalah.md](docs/panduan-pemecahan-masalah.md).

Panel administrasi tersedia di `/admin`. Buat akun admin melalui proses provisioning yang disetujui pengelola; jangan gunakan akun contoh atau memasukkan rahasia produksi ke Git.

## Fitur

- Pendaftaran pasien dan pengelolaan pegawai klinik
- Appointment, pemeriksaan, resep, dan stok obat
- Billing tindakan dengan katalog tarif default
- Transaksi, persetujuan pembayaran, dan unduhan struk PDF
- Dropdown diagnosis ICD-10 dari katalog lokal
- Fondasi sinkronisasi pasien dan appointment ke SATUSEHAT

## Katalog ICD-10

Importer menerima CSV atau XLSX dengan kolom `CODE` dan `DISPLAY` atau `DESCRIPTION`. Pastikan sumber dan hak penggunaan katalog sesuai sebelum mengimpor.

```sh
php artisan icd10:import "/path/to/catalog.xlsx" --catalog-version="versi katalog"
```

## Integrasi SATUSEHAT

Integrasi memerlukan registrasi fasilitas, kredensial resmi, dan ID Organization, Location, serta Practitioner. Petunjuk konfigurasi ada di [docs/satusehat.md](docs/satusehat.md). Pengiriman ke layanan belum aktif sampai kredensial dan mapping resmi dikonfigurasi serta diuji di sandbox.

## Queue

Jika `QUEUE_CONNECTION` menggunakan database atau Redis, jalankan worker:

```sh
php artisan queue:work
```

## Sumber dan lisensi

Lihat [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md) untuk provenance dan catatan lisensi source serta dependency.
