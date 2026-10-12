# Backup database SIMKLINIK

## Backup awal yang sudah dibuat

Backup saat ini berada di:

```text
storage/app/private/database-backups/simklinik-2026-10-04_011124.sql.gz
```

Folder ini bukan public storage. Backup berisi data sensitif pasien dan hanya boleh dapat dibaca oleh akun sistem yang berwenang.

## Membuat backup manual

Jalankan dari direktori aplikasi:

```sh
php artisan db:backup
```

Perintah membuat dump skema dan data tabel MySQL, mengompresnya sebagai `.sql.gz`, menyimpan dengan permission privat, dan mempertahankan 14 backup terakhir. Untuk mengubah jumlah salinan:

```sh
php artisan db:backup --keep=30
```

## Menjalankan hanya saat diperlukan

Backup tidak dijadwalkan otomatis. Jalankan `php artisan db:backup` ketika ingin membuat salinan baru, misalnya sebelum pembaruan besar atau secara rutin sesuai kebijakan klinik. Tidak perlu menambahkan cron atau scheduler untuk backup.

## Uji arsip dan pemulihan

Validasi file kompresi tanpa menampilkan isinya:

```sh
gzip -t storage/app/private/database-backups/nama-backup.sql.gz
```

Untuk pemulihan, gunakan database kosong/terisolasi terlebih dahulu dan akun MySQL yang berwenang. Contoh pada server Linux yang memiliki MySQL client:

```sh
gzip -dc storage/app/private/database-backups/nama-backup.sql.gz | mysql -h HOST -u USER -p NAMA_DATABASE
```

Import akan membuat ulang tabel dari file dan dapat mengganti tabel yang sudah ada. Jangan jalankan pada database produksi sebelum ada persetujuan perubahan, pemeriksaan target database, serta backup terkini. Setelah pemulihan, cek login, data pasien, appointment, EMR, transaksi, dan migrasi.

## Catatan penting

- Backup database tidak menyertakan file pada `storage/app/public` atau file unggahan lain; cadangkan file tersebut secara terpisah bila dibutuhkan.
- Backup pada disk server yang sama membantu menghadapi kerusakan data logis, tetapi tidak melindungi dari kehilangan server/disk. Salin backup terenkripsi ke media atau object storage di lokasi terpisah dengan akses terbatas.
- Simpan credential database di `.env`/secret manager server. Jangan menyimpan password di cron, repository, atau dokumentasi.
- Perintah ini mencadangkan tabel MySQL. Backup juga perlu dipantau: pastikan file terbaru ada, ukur ruang disk, dan uji restore berkala.
