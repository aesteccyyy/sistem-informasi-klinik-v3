# Integrasi SATUSEHAT

Integrasi mengirim resource FHIR R4 secara manual dari daftar pasien dan appointment. Tidak ada data pasien yang dikirim otomatis ketika record lokal dibuat atau diubah.

## Prasyarat SATUSEHAT

1. Daftarkan fasilitas pada platform SATUSEHAT dan selesaikan proses verifikasi/approval.
2. Dapatkan nomor IHS Organization fasilitas dari hasil registrasi/master data SATUSEHAT.
3. Buat resource `Location` melalui API onboarding sandbox dengan metode `POST`. Salin properti `id` dari respons sebagai `Location ID`; ID ini bukan nilai yang dicari di menu portal. Ikuti [dokumentasi API onboarding Location](https://satusehat.kemkes.go.id/platform/docs/api-catalogue/onboardings/apis/location/) dan [spesifikasi FHIR Location](https://satusehat.kemkes.go.id/platform/docs/id/fhir/resources/location/).
4. Dapatkan pasangan `Client ID`/`Client Secret` dari akses API sandbox.
5. Dapatkan `Practitioner ID` tiap dokter dari data tenaga kesehatan yang sudah terdaftar.
6. Pastikan pasien memiliki NIK 16 digit dan persetujuan pemrosesan data yang diperlukan.
7. Gunakan kode diagnosis ICD-10 yang sesuai dokumentasi profil SATUSEHAT.

## Konfigurasi lokal

Tambahkan nilai berikut ke `.env`. Default base URL menunjuk environment staging; kredensial production hanya digunakan setelah fasilitas disetujui.

```dotenv
SATUSEHAT_BASE_URL=https://api-satusehat-stg.dto.kemkes.go.id
SATUSEHAT_TOKEN_URL=
SATUSEHAT_FHIR_URL=
SATUSEHAT_CLIENT_ID=isi-client-id
SATUSEHAT_CLIENT_SECRET=isi-client-secret
SATUSEHAT_TIMEOUT=20
SATUSEHAT_KFA_BASE_URL=https://api-satusehat-stg.dto.kemkes.go.id/kfa-v2
SATUSEHAT_KFA_TOKEN_URL=https://api-satusehat-stg.dto.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials
SATUSEHAT_KFA_CLIENT_ID=isi-kfa-client-id
SATUSEHAT_KFA_CLIENT_SECRET=isi-kfa-client-secret
SATUSEHAT_KFA_ACCESS_TOKEN=
```

`SATUSEHAT_TOKEN_URL` dan `SATUSEHAT_FHIR_URL` boleh dikosongkan untuk memakai path default OAuth2 dan FHIR R4 di bawah base URL. Jangan commit credential ke git.

Pencarian KFA memakai API KFA v2 dan akses KFA yang disetujui Kemenkes. Aplikasi bisa mengambil token otomatis menggunakan pasangan `SATUSEHAT_KFA_CLIENT_ID`/`SATUSEHAT_KFA_CLIENT_SECRET`, atau memakai `SATUSEHAT_KFA_ACCESS_TOKEN` yang sudah diperoleh. Access token bersifat sementara dan perlu diperbarui setelah kedaluwarsa. Gunakan token sesuai environment (staging/production) dan jangan commit token atau credential ke git.

Jalankan migration dan bersihkan cache konfigurasi:

```sh
php artisan migrate
php artisan config:clear
```

## Mapping dan pengiriman

1. Siapkan katalog ICD-10 yang sumber dan versinya sesuai untuk fasilitas. Importer menerima `.xlsx` atau `.csv` dengan kolom `CODE` dan `DISPLAY`/`DESCRIPTION`, serta kolom opsional `VERSION`. Jangan gunakan contoh kode buatan sebagai katalog klinis.
2. Import katalog menggunakan `php artisan icd10:import "/path/to/ICD-10 e-klaim.xlsx"`. Versi dari kolom `VERSION` akan digunakan; opsi `--catalog-version="versi"` dapat dipakai untuk menetapkan satu versi bagi semua baris.
3. Di **Patients**, isi NIK dan data anamnesis pada bagian Family History, Allergies and Intolerances, dan Medication History. Gunakan kode ICD-10 untuk kondisi keluarga, SNOMED CT untuk alergen/reaksi, dan KFA untuk obat. Jangan mengarang kode klinis.
4. Isi NIK pasien pada form pasien, lalu gunakan aksi **Sync SATUSEHAT** di daftar pasien. Aksi ini menjadi pintu sinkronisasi lengkap: Patient ditautkan/diperbarui lewat IHS, anamnesis pasien dikirim sebagai FamilyMemberHistory, AllergyIntolerance, dan MedicationStatement, lalu seluruh appointment pasien diproses sebagai Encounter beserta diagnosis, pemeriksaan/vital signs, ClinicalImpression, Goal, dan CarePlan. ID remote dan status sinkronisasi disimpan.
5. Isi Organization ID dan Location ID dari SATUSEHAT serta unggah logo pada data klinik. Aplikasi memakai Location ID untuk membuat referensi `Location/{id}` pada Encounter; aplikasi tidak membuat resource Location.
6. Isi Practitioner ID dokter pada data employee.
7. Di **Appointments**, pilih diagnosis ICD-10 dan lengkapi bagian Anamnesis Conditions, Clinical Impression, Care Goals, serta Care Plans and Instructions. Physical and Functional Observations disembunyikan karena vital signs diambil otomatis dari TTV appointment (tinggi badan, berat badan, dan tekanan darah) saat sinkronisasi. Gunakan kode SNOMED CT untuk condition/prognosis/goal. Kategori CarePlan dibuat otomatis dari tipe rencana perawatan sebagai SNOMED CT. Medication statements memerlukan KFA code.
8. Tinggi, berat, dan tekanan darah pada data appointment otomatis dikirim sebagai Observation LOINC dengan unit UCUM; tekanan darah harus berformat sistolik/diastolik, misalnya `120/80`.
9. Sinkronisasi appointment juga dapat dijalankan dari daftar appointment untuk satu appointment tertentu. Jika memakai aksi pada daftar pasien, seluruh appointment pasien akan diproses otomatis. Sistem memastikan Patient terhubung, mengirim Encounter dan Condition diagnosis, lalu mengirim resource klinis tambahan beserta ID Encounter. ID tiap resource disimpan agar sinkron ulang memperbarui resource yang sama.
10. Jika gagal, lihat status dan tooltip error di daftar. Tombol sync dapat dicoba kembali setelah mapping atau credential diperbaiki.

Aksi menggunakan request langsung agar hasil/error dapat terlihat saat itu juga. Endpoint yang dipakai harus dicocokkan dengan environment dan dokumentasi FHIR SATUSEHAT yang berlaku untuk fasilitas sebelum production.

## Resep dan penyerahan obat

Katalog pada menu **Drugs** menyediakan pencarian otomatis nama, kekuatan, atau merek lewat API KFA v2. Pilihan menampilkan kode produk aktual `93xxxxxx` dan template `92xxxxxx`; resep non-racikan dapat memakai template atau produk aktual yang sesuai. Saat apotik menyerahkan obat, pilih produk aktual `93xxxxxx` yang benar-benar diserahkan. Jika pencarian belum aktif, periksa konfigurasi dan persetujuan akses KFA, atau gunakan KFA Browser sebagai rujukan manual. Jangan memakai kode zat aktif `91xxxxxx` sebagai kode produk non-racikan.

Dokter mengisi resep dari bagian resep pada Appointment: aturan pakai, dosis per kali, satuan dosis, frekuensi, rute, durasi, dan jumlah. Simpan resep untuk memasukkannya ke antrean apotik. Apotik membuka **Prescription** di kelompok **Medication Management**, memeriksa resep, lalu memilih **Verifikasi resep**, **Tolak resep**, atau **Serahkan obat**. Verifikasi mengirim paket Medication + MedicationRequest; penyerahan mengurangi stok dan mengirim Medication + MedicationDispense. Penyerahan sebagian dicatat sebagai jumlah kumulatif dan dapat dilanjutkan melalui aksi penyerahan berikutnya.

Sinkronisasi memerlukan IHS Organization dan Patient, Practitioner dokter, KFA yang benar, koneksi SATUSEHAT terkonfigurasi, serta Encounter appointment untuk pengiriman MedicationDispense. Sinkron resep dapat dilakukan setelah appointment dibuat; status dan pesan error tampil pada daftar Prescription. Setelah memperbaiki mapping/koneksi, gunakan aksi ulangi sinkronisasi yang tersedia.

Implementasi saat ini mendukung obat non-racikan. Bentuk sediaan menggunakan kode profil medication-form bila diisi. Racikan, perubahan/substitusi dengan terminologi terstruktur, data batch/kedaluwarsa, dan dokumen resep belum diimplementasikan. Validasi akhir payload harus dilakukan di sandbox dengan profil/use case fasilitas sebelum production; lihat [Medication](https://satusehat.kemkes.go.id/platform/docs/id/fhir/resources/medication/), [MedicationRequest](https://satusehat.kemkes.go.id/platform/docs/id/fhir/resources/medication-request/), [MedicationDispense](https://satusehat.kemkes.go.id/platform/docs/id/fhir/resources/medication-dispense/), serta [Pelayanan Kefarmasian](https://satusehat.kemkes.go.id/platform/docs/id/interoperability/kefarmasian/).

## Catatan profil

Payload mengikuti profil FHIR R4 SATUSEHAT. Encounter dan Condition diagnosis dikirim bersama dalam satu FHIR transaction Bundle, menggunakan referensi silang `urn:uuid`; kode ICD-10 disimpan pada `Condition.code` dan Encounter mereferensikan Condition melalui `Encounter.diagnosis`. Resource klinis tambahan memerlukan kode terminology valid dan dikirim setelah Encounter tersedia. Validasi payload terhadap sandbox dan contoh resmi platform tetap wajib sebelum production.
