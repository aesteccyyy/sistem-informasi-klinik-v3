# Panduan Menelusuri Error SIMKLINIK

Panduan ini membantu menemukan **bagian kode yang relevan** saat sebuah halaman bermasalah. Gunakan dari atas ke bawah: tentukan gejalanya, lapisan yang gagal, lalu periksa file sesuai peta.

## Alur penelusuran cepat

1. Catat nama menu/URL, langkah sebelum error, waktu kejadian, dan pesan error persisnya. Ambil screenshot tanpa data pasien yang tidak perlu.
2. Tentukan apakah masalah terjadi sebelum halaman terbuka, saat menampilkan data, ketika menyimpan, atau pada layanan eksternal.
3. Periksa log Laravel di `storage/logs/laravel.log`. Untuk error browser, buka Developer Tools → Console dan Network; cari request yang gagal.
4. Cocokkan halaman dengan tabel di bawah. Mulai dari `*Resource.php`, lalu periksa `Pages/` atau `RelationManagers/` untuk aksi spesifik.
5. Telusuri aturan akses di Policy, relasi/filter klinik di query, dan model/migrasi bila data salah atau tidak tersimpan.

> Jangan membagikan `.env`, password, token, NIK, atau isi rekam medis. Samarkan data pasien pada screenshot/log.

## Peta halaman ke file

| Halaman/menu | File utama yang diperiksa | Fokus pemeriksaan |
|---|---|---|
| Login `/admin/login` | `app/Filament/Pages/Auth/Login.php`; `resources/views/filament/pages/auth/login.blade.php`; `app/Providers/Filament/AdminPanelProvider.php` | Tampilan: Blade; autentikasi dan panel: Page/Provider; akun: User dan Policy. |
| Dashboard | `app/Filament/Pages/Dashboard.php`; `app/Filament/Widgets/PatientVisitsChart.php` | Grafik: Widget; akses/menu: Provider dan permission. |
| Pendaftaran | `app/Filament/Pages/RegistrationDesk.php`; `resources/views/filament/pages/registration-desk.blade.php` | Aksi: Page; tampilan: Blade. |
| Panggilan Antrian | `app/Filament/Pages/QueueBoard.php`; `resources/views/filament/pages/queue-board.blade.php` | Pemanggilan: Page; tampilan/interaksi browser: Blade/JavaScript. |
| Dashboard Panggilan | `app/Filament/Pages/QueueDashboard.php`; `resources/views/filament/pages/queue-dashboard.blade.php` | Data: Page; tampilan monitor: Blade. |
| Patients | `app/Filament/Resources/PatientResource.php`; `app/Filament/Resources/PatientResource/Pages/`; `app/Policies/PatientPolicy.php` | Form/list/detail: Resource/Pages; akses: Policy; riwayat: `RelationManagers/AppointmentsRelationManager.php`. |
| Employees / Users | `app/Filament/Resources/UserResource.php`; `app/Filament/Resources/UserResource/Pages/`; `app/Policies/UserPolicy.php` | Form/list: Resource/Pages; role/akses: Policy dan permission. |
| Roles / permissions | `app/Filament/Resources/Shield/RoleResource.php`; `app/Filament/Resources/Shield/RoleResource/Pages/`; `app/Policies/RolePolicy.php` | Role: Resource/Pages; otorisasi: Policy dan Filament Shield. |
| Appointment | `app/Filament/Resources/AppointmentResource.php`; `app/Filament/Resources/AppointmentResource/Pages/`; `app/Policies/AppointmentPolicy.php` | Form/status: Resource/Pages; resep dan tindakan: `RelationManagers/`; akses: Policy. |
| EMR | `app/Filament/Resources/EmrResource.php`; `app/Filament/Resources/EmrVisitResource.php`; direktori `Pages/` keduanya | Pencarian: EmrResource/ListEmrs; isian kunjungan: EmrVisitResource/Pages; relasi klinis: Appointment dan Models. |
| Prescription / Apotik | `app/Filament/Resources/PrescriptionResource.php`; `app/Filament/Resources/AppointmentResource/RelationManagers/PrescriptionsRelationManager.php`; `app/Services/PharmacyPrescriptionService.php`; `app/Policies/PrescriptionPolicy.php` | Input dokter: Appointment relation manager; antrean/verifikasi/serah/tolak: PrescriptionResource dan service; akses: Policy; integrasi: `app/Services/Satusehat/SatusehatMedicationSync.php`. |
| Drugs / persediaan | `app/Filament/Resources/DrugResource.php`; direktori `DrugResource/Pages/`; `app/Policies/DrugPolicy.php`; `app/Models/Drug.php` | Form/stok/KFA/impor: Resource/Pages; otorisasi: Policy; data: Model dan migrasi. |
| Drug Categories | `app/Filament/Resources/DrugCategoryResource.php`; direktori `Pages/`; `app/Policies/DrugCategoryPolicy.php` | Kategori: Resource/Pages/Policy. |
| Tarif Tindakan | `app/Filament/Resources/TreatmentServiceResource.php`; direktori `Pages/`; `app/Policies/TreatmentServicePolicy.php` | Tarif: Resource/Pages/Policy. |
| Kategori Tindakan | `app/Filament/Resources/TreatmentCategoryResource.php`; direktori `Pages/`; `app/Policies/TreatmentCategoryPolicy.php` | Kategori: Resource/Pages/Policy. |
| Transaction / pembayaran | `app/Filament/Resources/TransactionResource.php`; direktori `Pages/`; `RelationManagers/BillsRelationManager.php`; `app/Policies/TransactionPolicy.php` | Transaksi/persetujuan: Resource/Pages; rincian: RelationManager; akses: Policy. Pembuatan saat konfirmasi appointment: AppointmentResource dan Model terkait. |
| Unduh struk PDF | `routes/web.php`; `resources/views/pdf.blade.php` | Route/otorisasi: web.php; isi PDF: Blade; exception: Laravel log. |
| Clinic | `app/Filament/Resources/ClinicResource.php`; direktori `Pages/`; `RelationManagers/UsersRelationManager.php`; `app/Policies/ClinicPolicy.php` | Data/logo: Resource/Pages; pegawai: RelationManager; akses: Policy. |
| SATUSEHAT | `app/Filament/Resources/SatusehatPatientResource.php`; `app/Filament/Resources/SatusehatPatientResource/Pages/ListSatusehatPatients.php`; `app/Services/Satusehat/`; `app/Jobs/Satusehat/` | Daftar/kirim: Resource/Page; request: Service; antrean: Job; konfigurasi: `.env` dan `docs/satusehat.md`. |

## Gejala umum

| Gejala | Pemeriksaan awal |
|---|---|
| Halaman kosong, 500, atau gagal memuat | `storage/logs/laravel.log`, file Page/Resource terkait, Console/Network browser, dan versi kode/dependency. |
| 403 atau menu hilang | Permission Filament Shield, `app/Policies/`, relasi user-klinik (`ClinicUser`), serta filter klinik di query Resource/Page. |
| 404 | URL/route, deklarasi Resource/Page, `app/Providers/Filament/AdminPanelProvider.php`, atau `routes/web.php` untuk endpoint non-Filament. |
| Form gagal disimpan | `Resource.php` bagian `form()`, Page create/edit, validasi, `$fillable`/casts Model, lalu migrasi kolom. Periksa pesan validasi terlebih dahulu. |
| Data tidak muncul atau keliru | Query tabel di Resource/Page, filter klinik/tanggal, relasi Model, Policy, lalu data database. Pastikan filter telah diterapkan. |
| Tombol tidak bereaksi/tampilan rusak | Blade, `resources/js/app.js`, `resources/css/app.css`, Console browser, dan aksi Page/Resource. |
| Error SQL/tabel/kolom tidak ada | Log SQL, Model, `database/migrations/`, status migrasi. Hindari `migrate:fresh` pada database berisi data. |
| PDF gagal | `routes/web.php`, `resources/views/pdf.blade.php`, relasi transaksi, permission, dan exception DomPDF di log. |
| SATUSEHAT gagal | Log, `app/Services/Satusehat/`, `app/Jobs/Satusehat/`, mapping di `docs/satusehat.md`, konfigurasi dan worker queue. Jangan bagikan token. |
| KFA atau Medication ditolak | Cek kode dan display KFA produk, bentuk sediaan, IHS Organization/Patient/Practitioner, Encounter, pesan sinkron pada daftar resep, lalu payload di `SatusehatMedicationSync.php`. Resep non-racikan memakai KFA produk template/aktual; penyerahan memakai produk aktual. |
| Hanya gagal di server | Versi PHP/extension, dependency Composer, `.env` server, cache konfigurasi, permission `storage`/`bootstrap/cache`, dan log server. |

## Cara membaca susunan kode

- `app/Filament/Resources/*Resource.php`: form, tabel, filter, dan aksi modul.
- `app/Filament/Resources/*/Pages/`: halaman daftar, tambah, edit, dan detail.
- `app/Filament/Resources/*/RelationManagers/`: tabel relasi di halaman record.
- `app/Filament/Pages/`: halaman khusus seperti Dashboard, registrasi, dan antrean.
- `resources/views/`: template Blade dan PDF.
- `app/Models/` serta `database/migrations/`: struktur dan hubungan data.
- `app/Policies/`: aturan akses.
- `routes/web.php`: endpoint web non-Filament.
- `app/Services/`, `app/Jobs/`, `app/Console/Commands/`: integrasi, antrean kerja, dan perintah CLI.
- `database/migrations/`: termasuk migrasi KFA, status proses apotik, dan pergerakan stok.
- `app/Providers/Filament/AdminPanelProvider.php`: panel, halaman terdaftar, menu, middleware, dan konfigurasi Filament.

## Format laporan bug

Sertakan nama menu/URL, tanggal dan jam beserta zona waktu, role pengguna, langkah reproduksi, hasil yang diharapkan, hasil aktual, pesan error/status request, dan apakah terjadi pada pengguna atau klinik lain. Lampirkan screenshot yang sudah disamarkan. Catat file/fungsi yang diperiksa dan potongan log yang sudah dibersihkan dari data pribadi/rahasia.
