<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 18mm 17mm 18mm; }
        @page:first { margin: 0; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #263247; font-size: 9.4pt; line-height: 1.48; }
        h1,h2,h3,h4,p { margin-top: 0; }
        h1 { font-size: 24pt; line-height: 1.2; color: #142747; margin-bottom: 8px; }
        h2 { font-size: 16pt; color: #142747; border-bottom: 2px solid #1aa99d; padding-bottom: 6px; margin: 0 0 13px; }
        h3 { font-size: 11.5pt; color: #1b5872; margin: 15px 0 6px; }
        h4 { font-size: 10pt; color: #253c5a; margin: 10px 0 5px; }
        p { margin-bottom: 8px; }
        ul,ol { margin: 4px 0 10px 17px; padding: 0; }
        li { margin: 0 0 4px; }
        .cover { height: 297mm; padding: 26mm 22mm; background: #f2f6fc; position: relative; }
        .cover-band { background: #15345b; color: #fff; padding: 19mm 15mm 16mm; border-radius: 6px; }
        .cover-band .eyebrow { color: #86e1d4; text-transform: uppercase; letter-spacing: 2px; font-size: 9pt; font-weight: bold; }
        .cover-band h1 { color: white; font-size: 31pt; margin: 13mm 0 5mm; }
        .cover-band .sub { color: #d5e2f3; font-size: 13pt; }
        .cover-visual { margin-top: 19mm; padding: 10mm; background: #fff; border: 1px solid #dbe5ef; border-radius: 7px; }
        .cover-visual .nodes td { width: 25%; padding: 8px 5px; text-align: center; font-size: 8pt; font-weight: bold; }
        .node { background: #e8f7f5; border: 1px solid #bce8e2; border-radius: 5px; padding: 10px 5px; color: #136c68; }
        .cover-meta { margin-top: 21mm; color: #5c6e85; font-size: 10pt; }
        .page { page-break-before: always; }
        .small { color: #63748b; font-size: 8pt; }
        .callout { padding: 9px 11px; background: #edf8f7; border-left: 4px solid #1aa99d; margin: 9px 0 12px; }
        .warning { padding: 9px 11px; background: #fff6e7; border-left: 4px solid #e3a53a; margin: 9px 0 12px; }
        .danger { padding: 9px 11px; background: #fff0f0; border-left: 4px solid #d25c62; margin: 9px 0 12px; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0 12px; }
        th { background: #15345b; color: #fff; text-align: left; font-size: 8.5pt; padding: 7px; }
        td { border: 1px solid #d9e1eb; padding: 6px 7px; vertical-align: top; }
        tr:nth-child(even) td { background: #f7f9fc; }
        .flow td { width: 20%; text-align: center; vertical-align: middle; padding: 9px 5px; background: #eef5fb; border: 1px solid #cedbea; font-weight: bold; color: #234466; }
        .flow .arrow { width: 5%; background: #fff; color: #1aa99d; font-size: 16pt; border: 0; padding: 0; }
        .flow-note { background: #f3f8fb; border: 1px solid #d8e4ec; padding: 8px; margin: 7px 0 14px; }
        .step { display: inline-block; background: #1aa99d; color: white; border-radius: 50%; width: 19px; height: 19px; line-height: 19px; text-align: center; font-weight: bold; margin-right: 5px; }
        .toc td:first-child { width: 12%; color: #1b817c; font-weight: bold; }
        .footer-note { border-top: 1px solid #dce4ec; padding-top: 8px; margin-top: 15px; color: #77859a; font-size: 8pt; }
        .no-break { page-break-inside: avoid; }
        .pill { background: #e7f4f3; padding: 2px 5px; color: #136c68; font-weight: bold; }
    </style>
</head>
<body>
    <section class="cover">
        <div class="cover-band">
            <div class="eyebrow">SIMKLINIK · Nusahome Lab</div>
            <h1>Panduan Pengguna<br>dan Administrator</h1>
            <div class="sub">Sistem Informasi Klinik</div>
        </div>
        <div class="cover-visual">
            <h3 style="text-align:center;margin-top:0">Satu alur layanan, dari pendaftaran sampai pembayaran</h3>
            <table class="nodes"><tr>
                <td><div class="node">Data Pasien</div></td><td><div class="node">Appointment</div></td><td><div class="node">EMR / SOAP</div></td><td><div class="node">Resep & Billing</div></td>
            </tr></table>
            <table class="flow"><tr><td>Registrasi</td><td class="arrow">›</td><td>Pelayanan</td><td class="arrow">›</td><td>Dokumentasi</td><td class="arrow">›</td><td>Pembayaran</td></tr></table>
            <p class="small" style="text-align:center;margin-bottom:0">Termasuk pengelolaan obat, laporan operasional, dan pengiriman data SATUSEHAT.</p>
        </div>
        <div class="cover-meta">Versi dokumentasi: 1.0<br>Dibuat: {{ $generatedAt }}<br><br>Dokumen ini menjelaskan fitur aplikasi berdasarkan implementasi yang tersedia.</div>
    </section>

    <section class="page">
        <h2>Daftar Isi</h2>
        <table class="toc">
            <tr><td>01</td><td>Gambaran umum aplikasi dan alur layanan</td></tr>
            <tr><td>02</td><td>Akses, login, menu, dan peran pengguna</td></tr>
            <tr><td>03</td><td>Master data pasien, pegawai, dan klinik</td></tr>
            <tr><td>04</td><td>Appointment dan nomor kunjungan</td></tr>
            <tr><td>05</td><td>Pengisian EMR dan SOAP</td></tr>
            <tr><td>06</td><td>Resep, tindakan, dan persediaan obat</td></tr>
            <tr><td>07</td><td>Transaksi, pembayaran, dan struk</td></tr>
            <tr><td>08</td><td>Dashboard dan pencarian riwayat</td></tr>
            <tr><td>09</td><td>Pengiriman data ke SATUSEHAT</td></tr>
            <tr><td>10</td><td>Pengaturan katalog tarif dan diagnosis</td></tr>
            <tr><td>11</td><td>Administrasi teknis, backup, dan keamanan</td></tr>
            <tr><td>12</td><td>Pemecahan masalah dan glosarium</td></tr>
        </table>
        <div class="callout"><b>Cara menggunakan panduan:</b> staf operasional dapat membaca bagian 2–8. Admin klinik membaca seluruh bagian. Konfigurasi server dan kredensial SATUSEHAT sebaiknya ditangani personel teknis yang diberi wewenang.</div>

        <h3>Dokumen dan batasan</h3>
        <p>Panduan ini mengikuti struktur aplikasi SIMKLINIK saat dokumen dibuat. Nama tombol atau hak akses dapat berbeda jika administrator mengubah permission atau konfigurasi panel. Antarmuka menampilkan beberapa label berbahasa Inggris karena belum seluruh label diterjemahkan.</p>
        <p>Aplikasi adalah alat pencatatan operasional dan klinis. Isi diagnosis, kode terminologi, resep, keputusan medis, dan status pembayaran harus diperiksa oleh petugas yang bertanggung jawab.</p>
    </section>

    <section class="page">
        <h2>01 · Gambaran Umum Aplikasi</h2>
        <p>SIMKLINIK membantu klinik mengelola identitas pasien, jadwal kunjungan, dokumentasi pemeriksaan, obat dan tindakan, tagihan, pembayaran, serta integrasi data pasien/kunjungan dengan SATUSEHAT.</p>
        <h3>Alur layanan klinik</h3>
        <table class="flow"><tr><td>1<br>Pasien dibuat / dicari</td><td class="arrow">›</td><td>2<br>Appointment dibuat</td><td class="arrow">›</td><td>3<br>Dokter isi EMR</td><td class="arrow">›</td><td>4<br>Resep & tindakan</td><td class="arrow">›</td><td>5<br>Tagihan dibayar</td></tr></table>
        <div class="flow-note"><b>Integrasi eksternal:</b> data pasien dikirim melalui menu <span class="pill">Kirim Data Pasien</span>; data kunjungan klinis dikirim dari Appointment setelah data klinis dan pemetaan SATUSEHAT disiapkan.</div>
        <h3>Bagian aplikasi</h3>
        <table><tr><th>Bagian</th><th>Fungsi utama</th><th>Contoh data</th></tr>
            <tr><td>Administrasi</td><td>Pengguna, role, klinik, pasien</td><td>Akun, identitas, alamat, hubungan pegawai-klinik</td></tr>
            <tr><td>Pelayanan</td><td>Appointment dan EMR</td><td>Tanggal, dokter, nomor kunjungan, vital sign, SOAP</td></tr>
            <tr><td>Farmasi & billing</td><td>Obat, resep, tindakan, transaksi</td><td>Stok, jumlah, harga, diskon, status pembayaran</td></tr>
            <tr><td>Interoperabilitas</td><td>Sinkronisasi SATUSEHAT</td><td>IHS ID, Organization/Location/Practitioner ID, status kirim</td></tr>
        </table>
        <div class="warning"><b>Pemisahan identitas:</b> setiap pasien memiliki patient ID internal dan nomor RM tetap enam digit. Patient ID tidak ditampilkan; nomor RM tampil pada daftar EMR, sementara nomor kunjungan tetap menjadi identitas terpisah untuk setiap appointment. Data induk pasien tetap satu untuk layanan rawat jalan, rawat inap, dan IGD.</div>
    </section>

    <section class="page">
        <h2>02 · Login, Menu, dan Peran</h2>
        <h3>Masuk ke aplikasi</h3>
        <ol><li>Buka alamat aplikasi. Halaman depan mengarahkan langsung ke formulir login.</li><li>Masukkan email dan kata sandi akun yang telah dibuat administrator.</li><li>Setelah berhasil, panel administrasi terbuka. Menu dan data yang terlihat mengikuti role, permission, dan hubungan pengguna dengan klinik.</li></ol>
        <h3>Peta menu</h3>
        <table><tr><th>Kelompok menu</th><th>Menu / tujuan</th></tr>
            <tr><td>Dashboard</td><td>Grafik jumlah appointment/kunjungan berdasarkan tanggal selama 7 hari terakhir.</td></tr>
            <tr><td>User Management</td><td>Role, Employees, Patients.</td></tr>
            <tr><td>Appointment Management</td><td>Appointment, Prescription, Transaction.</td></tr>
            <tr><td>Pelayanan</td><td>Panggilan Antrian untuk petugas dan Dashboard Panggilan khusus tampilan monitor.</td></tr>
            <tr><td>Clinical Management</td><td>EMR untuk mencari appointment pasien dan mengisi atau membuka rekam medis.</td></tr>
            <tr><td>Clinic Management</td><td>Data klinik, logo, identitas organisasi SATUSEHAT, serta relasi pegawai-klinik.</td></tr>
            <tr><td>Medication Management</td><td>Drugs dan Drug Categories. Daftar obat dapat diimpor/ekspor sebagai CSV yang kompatibel dengan Excel.</td></tr>
            <tr><td>Billing Settings</td><td>Kategori tindakan dan tarif tindakan.</td></tr>
            <tr><td>SATUSEHAT</td><td>Kirim Data Pasien, status sinkronisasi, dan tindakan pengiriman pasien terpilih.</td></tr>
        </table>
        <h3>Peran yang tersedia</h3>
        <table><tr><th>Role</th><th>Peruntukan umum</th><th>Catatan akses</th></tr>
            <tr><td>Super Admin</td><td>Administrasi seluruh aplikasi dan klinik.</td><td>Akses lintas klinik sesuai permission.</td></tr>
            <tr><td>Admin</td><td>Pengelolaan akun, klinik, data master, dan operasional.</td><td>Dapat mengelola beberapa modul administratif.</td></tr>
            <tr><td>Operator</td><td>Registrasi pasien dan pengelolaan kunjungan yang diizinkan.</td><td>Cakupan data mengikuti assignment klinik dan permission.</td></tr>
            <tr><td>Doctor</td><td>Pelayanan klinis dan pengisian EMR.</td><td>Appointment dokter pada klinik yang ditautkan.</td></tr>
            <tr><td>Pharmacist</td><td>Pengelolaan obat dan resep.</td><td>Akses bergantung pada assignment dan permission farmasi.</td></tr>
        </table>
        <div class="callout">Role bawaan bukan satu-satunya penentu akses. Filament Shield menyimpan permission per role, sedangkan sejumlah daftar membatasi hasil berdasarkan klinik pengguna.</div>
    </section>

    <section class="page">
        <h2>03 · Master Data Pasien, Pegawai, Klinik</h2>
        <h3>Pasien</h3>
        <p>Menu <b>Patients</b> menyimpan identitas pasien dan data pendukung: nama, NIK, nomor telepon, tanggal lahir, jenis kelamin, umur terhitung, status perkawinan, golongan darah, alamat, serta data anamnesis yang diperlukan integrasi.</p>
        <ol><li>Buka Patients, pilih tambah pasien, lalu lengkapi identitas sesuai dokumen pasien.</li><li>NIK divalidasi 16 digit jika diisi; telepon dan NIK perlu unik.</li><li>Isi tanggal lahir dan data demografis seakurat mungkin.</li><li>Data riwayat keluarga, alergi/intoleransi, dan riwayat obat berada pada bagian terpisah yang dapat dibuka saat diperlukan.</li><li>Simpan pasien. Untuk menautkan pasien SATUSEHAT, isi IHS Patient ID bila sudah tersedia atau kirim melalui menu SATUSEHAT.</li></ol>
        <div class="warning"><b>Privasi:</b> NIK, riwayat kesehatan, dan kredensial adalah data sensitif. Jangan menyalin data pasien ke spreadsheet pribadi atau mengirimkannya melalui kanal tanpa izin.</div>
        <h3>Pegawai dan dokter</h3>
        <p>Menu <b>Employees</b> mengelola akun, email login, nomor kontak, role, dan relasi klinik. Data dokter dapat memiliki fee dan Practitioner IHS ID untuk SATUSEHAT. Administrator membuat akun dan menetapkan akses yang sesuai.</p>
        <h3>Klinik</h3>
        <p>Menu <b>Clinic</b> menyimpan nama, tipe, alamat, logo, SATUSEHAT Organization ID, dan Location ID. Relasi dokter/pegawai dengan klinik menentukan dokter yang tersedia saat appointment dan cakupan data sejumlah pengguna.</p>
        <h3>Relasi data</h3>
        <table class="flow"><tr><td>Pasien<br>identitas jangka panjang</td><td class="arrow">→</td><td>Appointment<br>tanggal + klinik + dokter</td><td class="arrow">→</td><td>EMR<br>catatan per kunjungan</td></tr></table>
    </section>

    <section class="page">
        <h2>04 · Appointment dan Nomor Kunjungan</h2>
        <p>Appointment dipakai untuk membuat jadwal kunjungan dan menghubungkan pasien ke dokter serta klinik. Menu ini bukan tempat utama mencari/mengisi SOAP; pengisian EMR dilakukan dari menu EMR.</p>
        <h3>Membuat appointment</h3>
        <ol><li>Buka <b>Appointment</b> lalu pilih tambah.</li><li>Pilih pasien yang sudah terdaftar.</li><li>Pilih pasangan klinik dan dokter.</li><li>Tentukan tanggal kunjungan, lalu simpan.</li><li>Appointment mendapat nomor kunjungan, kode antrian, dan status awal <span class="pill">SCHEDULED</span>.</li></ol>
        <p>Kode antrian menggunakan format <b>A-001</b> dan berurutan berdasarkan appointment yang dibuat untuk tanggal dan klinik yang sama. Urutan dimulai kembali pada tanggal berikutnya. Kode tampil pada daftar Appointment, EMR, dan halaman Panggilan Antrian.</p>
        <h3>Halaman Panggilan dan Dashboard Panggilan</h3>
        <p>Menu <b>Panggilan Antrian</b> dipakai petugas untuk memilih klinik/tanggal dan mengelola nomor. Petugas berizin dapat memilih <b>Panggil Berikutnya</b>; halaman menampilkan kode yang sedang dipanggil dan menyebutnya melalui browser bila audio tersedia. <b>Panggil Ulang</b> mengulang panggilan. <b>Lewati</b> menandai nomor saat ini dilewati. Saat nomor berikutnya dipanggil, nomor sebelumnya otomatis ditandai selesai.</p>
        <p>Menu <b>Dashboard Panggilan</b> adalah halaman terpisah untuk monitor/TV: hanya menampilkan kode yang sedang dipanggil dan daftar antrian berikutnya, tanpa tombol untuk mengubah status. Halaman petugas menyediakan tautan untuk membukanya di tab baru.</p>
        <h3>Status appointment</h3>
        <table><tr><th>Status</th><th>Makna operasional</th></tr>
            <tr><td>Scheduled</td><td>Appointment tercatat; menunggu pemeriksaan atau EMR.</td></tr>
            <tr><td>Diagnosed</td><td>Pemeriksaan klinis selesai dicatat.</td></tr>
            <tr><td>Prepared</td><td>Resep disiapkan.</td></tr>
            <tr><td>Confirmed</td><td>Kunjungan dikonfirmasi selesai; sistem membuat transaksi tagihan bila transaksi belum ada.</td></tr>
        </table>
        <div class="callout"><b>Nomor kunjungan:</b> setiap appointment memiliki nomor kunjungan sendiri. Nomor RM tetap melekat pada data induk pasien dan tidak ditampilkan. Riwayat lintas kunjungan ditelusuri melalui pasien/EMR.</div>
        <div class="danger"><b>Perhatian:</b> konfirmasi appointment memicu pembuatan transaksi dari item tindakan dan resep saat itu. Pastikan detail billing diperiksa sebelum status diubah ke Confirmed.</div>
        <h3>Daftar Appointment</h3>
        <p>Daftar menampilkan pasien, klinik, dokter, tanggal, nomor kunjungan, vital sign, status appointment, dan status sinkronisasi pasien SATUSEHAT. Filter status tersedia; gunakan pencarian tabel untuk menemukan data.</p>
    </section>

    <section class="page">
        <h2>05 · EMR dan SOAP</h2>
        <p>Menu <b>EMR</b> menampilkan appointment untuk pengisian/pembukaan rekam medis. Secara bawaan daftar menampilkan tanggal hari ini. Tanggal dapat dihapus untuk melihat semua kunjungan. Pencarian pasien mendukung nama dan/atau tanggal lahir.</p>
        <h3>Mencari dan membuka EMR</h3>
        <ol><li>Buka menu EMR.</li><li>Tentukan tanggal kunjungan; kosongkan tanggal untuk riwayat lintas tanggal.</li><li>Isi nama pasien atau tanggal lahir sebagai kriteria tambahan.</li><li>Klik <b>Cari</b> untuk menerapkan filter.</li><li>Pilih <b>Isi / Buka EMR</b> pada baris appointment pasien yang benar.</li></ol>
        <h3>Isian pemeriksaan</h3>
        <table><tr><th>Bagian</th><th>Isi</th><th>Contoh panduan</th></tr>
            <tr><td>Pemeriksaan fisik</td><td>Tinggi, berat, tekanan darah</td><td>Tekanan darah menggunakan format sistolik/diastolik, misalnya 120/80.</td></tr>
            <tr><td>S · Subjective</td><td>Keluhan/anamnesis pasien</td><td>Catat keluhan dan konteks berdasarkan wawancara klinis.</td></tr>
            <tr><td>O · Objective</td><td>Temuan pemeriksaan</td><td>Catat hasil pemeriksaan objektif.</td></tr>
            <tr><td>A · Assessment</td><td>Penilaian atau diagnosis</td><td>Pilih diagnosis ICD-10 yang sesuai bila akan dikirim ke SATUSEHAT.</td></tr>
            <tr><td>P · Plan</td><td>Rencana perawatan</td><td>Catat terapi, instruksi, atau tindak lanjut.</td></tr>
        </table>
        <p>Form EMR juga menyediakan bagian Diagnosis Klinis, Target Perawatan, Rencana dan Instruksi Perawatan, resep, serta tindakan. Isian dengan terminology SATUSEHAT harus menggunakan kode resmi yang sesuai; jangan mengarang kode.</p>
        <h3>Menutup alur klinis</h3>
        <p>Perbarui status mengikuti tahapan pelayanan. Saat transaksi telah dibuat dan kunjungan selesai, status Confirmed digunakan sebagai penanda konfirmasi. Catatan klinis harus disimpan pada appointment yang benar.</p>
    </section>

    <section class="page">
        <h2>06 · Resep, Tindakan, dan Stok Obat</h2>
        <h3>Resep dari EMR/Appointment</h3>
        <ol><li>Buka appointment yang sesuai melalui EMR.</li><li>Pada bagian resep, pilih obat untuk klinik appointment tersebut.</li><li>Isi aturan pakai, dosis, satuan dosis, frekuensi, rute, durasi, jumlah, dan catatan bila ada, lalu simpan sebagai order ke apotik.</li><li>Periksa daftar Prescription untuk memantau verifikasi dan penyerahan.</li></ol>
        <p>Stok tidak dipotong ketika dokter menyimpan resep. Apotik memeriksa order, lalu mencatat jumlah yang benar-benar diserahkan; stok berkurang pada saat penyerahan dan dapat diproses sebagian.</p>
        <h3>Pengelolaan master obat</h3>
        <p>Menu <b>Drugs</b> memuat Klinik, Kategori, SKU, Nama, Deskripsi, Stok, Harga, status tersedia, kode/nama KFA, dan bentuk sediaan. Untuk Medication non-racikan, isi KFA produk yang tepat—template (92xxxxxx) atau aktual (93xxxxxx). Apotik memakai KFA produk aktual ketika menyerahkan obat.</p>
        <h4>Impor/ekspor spreadsheet</h4>
        <ul><li>Pilih <b>Ekspor ke Excel (CSV)</b>, lalu tentukan klinik. File dapat dibuka dengan Excel.</li><li>Untuk impor, simpan spreadsheet sebagai <b>CSV UTF-8</b> dengan pemisah titik koma (;).</li><li>Kolom Nama dan Kategori wajib; KFA Code, KFA Name, KFA System, Bentuk Sediaan, dan Kode Bentuk Sediaan dapat diisi dari katalog resmi.</li><li>Pilih klinik tujuan sebelum mengunggah. SKU yang sama pada klinik itu akan memperbarui record; tanpa SKU akan menambah record baru.</li><li>Validasi yang gagal membatalkan seluruh file. Perbaiki baris yang dilaporkan, lalu impor ulang.</li></ul>
        <div class="warning">CSV bukan format XLSX asli. Jangan gunakan fitur simpan sebagai CSV jika perlu mempertahankan beberapa sheet, formula, atau format kompleks; hanya data tabel sederhana yang diimpor.</div>
        <h3>Tarif dan billing tindakan</h3>
        <p>Administrator mengelola <b>Tarif Tindakan</b> dan kategori billing. Pada appointment, item tarif aktif dapat dipilih sebagai dasar nama/deskripsi/harga. Nilai masih dapat disesuaikan sebelum appointment dikonfirmasi.</p>
        <h3>Prescription terpisah</h3>
        <p>Dokter membuat resep melalui bagian resep pada EMR/appointment. Apotik membuka menu <b>Prescription</b> di kelompok Medication Management untuk memverifikasi, menolak dengan alasan, atau menyerahkan obat. Jumlah diserahkan dan catatan apotik tercatat. Kunjungan baru dapat ditutup setelah semua resep diserahkan atau ditolak; tagihan obat dihitung dari jumlah yang diserahkan.</p>
    </section>

    <section class="page">
        <h2>07 · Transaksi, Pembayaran, dan Struk</h2>
        <h3>Terbentuknya tagihan</h3>
        <table class="flow"><tr><td>Tindakan dan resep dicatat</td><td class="arrow">→</td><td>Status appointment dikonfirmasi</td><td class="arrow">→</td><td>Transaksi + rincian bill dibuat</td><td class="arrow">→</td><td>Pembayaran disetujui</td></tr></table>
        <p>Saat status appointment berubah ke Confirmed, sistem membuat transaksi satu kali jika transaksi untuk appointment tersebut belum ada. Transaksi menyimpan ringkasan pasien/dokter dan total tindakan/resep. Bill berisi rincian item.</p>
        <h3>Menandai pembayaran</h3>
        <ol><li>Buka menu <b>Transaction</b> dan cari transaksi pasien/appointment.</li><li>Verifikasi identitas, rincian tagihan, total, dan bukti pembayaran sesuai prosedur klinik.</li><li>Gunakan aksi <b>Approve Payment</b> untuk mengubah status dari Unpaid menjadi Paid.</li><li>Unduh struk melalui aksi PDF bila diperlukan.</li></ol>
        <table><tr><th>Status</th><th>Arti</th></tr><tr><td>Unpaid</td><td>Belum ditandai lunas di aplikasi.</td></tr><tr><td>Paid</td><td>Pembayaran sudah disetujui dan waktu pembayaran dicatat.</td></tr></table>
        <div class="danger"><b>Kontrol keuangan:</b> akses persetujuan pembayaran sebaiknya dibatasi. Status Paid merupakan catatan administratif aplikasi dan bukan konfirmasi otomatis dari bank/payment gateway.</div>
    </section>

    <section class="page">
        <h2>08 · Dashboard dan Pencarian</h2>
        <h3>Grafik dashboard</h3>
        <p>Dashboard menampilkan grafik batang jumlah appointment untuk tujuh hari terakhir, dikelompokkan menurut schedule date. Grafik menghitung record appointment, termasuk yang belum selesai diperiksa; aplikasi belum mempunyai status kehadiran terpisah yang dapat membedakan booking dari pasien hadir.</p>
        <table class="flow"><tr><td>Hari -6</td><td class="arrow">→</td><td>Hari -5</td><td class="arrow">→</td><td>…</td><td class="arrow">→</td><td>Hari ini</td></tr></table>
        <p class="small">Tinggi batang mewakili jumlah appointment pada tanggal yang bersangkutan. Pengguna non-admin dibatasi ke klinik yang ditautkan.</p>
        <h3>Pencarian EMR</h3>
        <table><tr><th>Filter</th><th>Hasil</th></tr><tr><td>Tanggal diisi</td><td>Appointment pada tanggal terpilih (default hari ini).</td></tr><tr><td>Tanggal dikosongkan</td><td>Riwayat appointment di semua tanggal yang boleh dilihat pengguna.</td></tr><tr><td>Nama pasien</td><td>Pencarian sebagian nama pasien.</td></tr><tr><td>Tanggal lahir</td><td>Kecocokan tanggal lahir pasien.</td></tr><tr><td>Nama dan tanggal lahir</td><td>Keduanya dipakai bersama untuk mempersempit hasil.</td></tr></table>
        <p>Filter perlu diterapkan dengan tombol Cari. Kolom pencarian tabel Filament, jika tersedia, dapat digunakan terpisah dari filter pasien/tanggal.</p>
    </section>

    <section class="page">
        <h2>09 · Pengiriman Data SATUSEHAT</h2>
        <p>Integrasi mengirim resource FHIR R4 secara manual. Membuat atau mengubah record lokal tidak otomatis mengirim data. Gunakan petunjuk ini bersama dokumen teknis <b>docs/satusehat.md</b> dan dokumentasi resmi SATUSEHAT.</p>
        <h3>Persiapan satu kali</h3>
        <ol><li>Pastikan fasilitas terdaftar dan environment sandbox/production telah ditetapkan.</li><li>Siapkan Client ID/Secret, base URL, Organization ID, Location ID, dan Practitioner IHS ID tiap dokter.</li><li>Isi NIK pasien dan pastikan persetujuan/pemrosesan data sesuai kebijakan fasilitas.</li><li>Siapkan katalog ICD-10 bersumber resmi; terminologi klinis lain seperti SNOMED CT dan KFA juga wajib menggunakan kode yang valid.</li><li>Isi riwayat keluarga, alergi, dan obat pada data pasien bila relevan.</li></ol>
        <h3>Mengirim pasien</h3>
        <ol><li>Buka <b>Kirim Data Pasien</b>.</li><li>Cari/filter pasien, pilih satu atau beberapa pasien.</li><li>Jalankan aksi pengiriman melalui tombol send.</li><li>Periksa status per pasien. Jika gagal, baca pesan error, koreksi mapping/identitas/kredensial, lalu coba ulang.</li></ol>
        <h3>Mengirim appointment dan data klinis</h3>
        <ol><li>Lengkapi identitas pasien dan koneksi SATUSEHAT.</li><li>Lengkapi appointment, vital sign, diagnosis ICD-10, serta data klinis yang akan dipetakan.</li><li>Pastikan Organization, Location, dan Practitioner ID benar.</li><li>Gunakan aksi Sync SATUSEHAT pada appointment.</li><li>Periksa status, waktu, dan pesan error pada daftar. Perbaiki kemudian sync ulang bila diperlukan.</li></ol>
        <table><tr><th>Status yang terlihat</th><th>Makna ringkas</th><th>Tindakan</th></tr>
            <tr><td>Belum dikirim / pending</td><td>Belum berhasil disinkronkan.</td><td>Periksa kesiapan identitas, kredensial, dan mapping.</td></tr>
            <tr><td>Sedang dikirim</td><td>Proses sync sedang berjalan.</td><td>Tunggu hasil, jangan klik berulang kali.</td></tr>
            <tr><td>Terkirim / synced</td><td>Integrasi lokal mencatat berhasil.</td><td>Catat ID remote/status; tetap lakukan pemeriksaan yang diwajibkan.</td></tr>
            <tr><td>Gagal</td><td>API atau validasi menolak request.</td><td>Baca tooltip/detail error, koreksi lalu ulangi.</td></tr>
        </table>
        <h3>Mengirim resep dan penyerahan obat</h3>
        <p>Saat apotik memverifikasi resep, aplikasi mengirim Medication + MedicationRequest. Saat obat diserahkan, aplikasi mengirim Medication + MedicationDispense yang merujuk ke MedicationRequest. Pastikan pasien dan appointment sudah tersinkron, kode KFA benar, serta Organization dan Practitioner telah dipetakan. MedicationRequest non-racikan memakai KFA template (92) atau aktual (93); MedicationDispense memakai KFA aktual (93). Jika pengiriman gagal, perbaiki pesan error lalu ulangi sinkronisasi pada daftar Prescription.</p>
        <div class="danger">Jangan mengirim data klinis produksi sebelum konfigurasi dan payload tervalidasi sesuai environment dan profil resmi. Jangan membagikan Client Secret di dokumen atau tiket dukungan.</div>
    </section>

    <section class="page">
        <h2>10 · Katalog Diagnosis dan Tarif</h2>
        <h3>Import katalog ICD-10</h3>
        <p>Diagnosis di EMR menggunakan katalog ICD-10 lokal. Importer menerima XLSX atau CSV dengan kolom <b>CODE</b> dan <b>DISPLAY</b> atau <b>DESCRIPTION</b>; kolom <b>VERSION</b> opsional.</p>
        <pre style="background:#f2f5f8;padding:9px;border:1px solid #dce3eb;font-size:8pt">php artisan icd10:import "/path/ke/katalog.xlsx" --catalog-version="versi-katalog"</pre>
        <p>Pastikan sumber, versi, dan hak penggunaan katalog telah diverifikasi. Jangan menggunakan contoh kode buatan untuk diagnosis pasien.</p>
        <h3>Kategori dan tarif tindakan</h3>
        <ul><li>Buat kategori tindakan/billing sebagai referensi pencatatan.</li><li>Masukkan tarif tindakan di menu Tarif Tindakan, beri nama, harga, kategori, dan status aktif.</li><li>Tarif aktif tersedia sebagai nilai awal di billing tindakan appointment; periksa harga final sebelum konfirmasi.</li></ul>
        <h3>Data pasien lokal dan IHS</h3>
        <p>IHS ID SATUSEHAT berbeda dari NIK dan ID database internal. Jangan mengisi ID pasien remote secara spekulatif. Gunakan IHS ID dari hasil integrasi yang sah atau mekanisme pencarian resmi pada proses sync.</p>
    </section>

    <section class="page">
        <h2>11 · Panduan Administrator Teknis</h2>
        <h3>Komponen aplikasi</h3>
        <table><tr><th>Komponen</th><th>Kebutuhan yang tercatat di proyek</th></tr>
            <tr><td>Backend</td><td>PHP 8.2+, Laravel 11, Filament 3</td></tr>
            <tr><td>Database</td><td>MySQL pada panduan instalasi proyek; migration tersedia untuk skema.</td></tr>
            <tr><td>Frontend assets</td><td>Node.js/npm untuk build asset.</td></tr>
            <tr><td>PDF</td><td>DomPDF untuk struk dan panduan.</td></tr>
            <tr><td>Integrasi</td><td>HTTP ke endpoint OAuth2/FHIR SATUSEHAT dengan credential fasilitas.</td></tr>
        </table>
        <h3>Instalasi lokal / lingkungan baru</h3>
        <pre style="background:#f2f5f8;padding:9px;border:1px solid #dce3eb;font-size:8pt">composer install
npm install
cp .env.example .env
php artisan key:generate
# Isi konfigurasi koneksi database pada .env
php artisan migrate
php artisan storage:link
npm run build
php artisan serve</pre>
        <p>Panel administrasi berada di <b>/admin</b>. Buat/provision akun admin melalui proses internal yang disetujui; repository tidak menetapkan akun demo untuk penggunaan nyata.</p>
        <h3>Pengaturan yang wajib diamankan</h3>
        <ul><li>Simpan APP_KEY, password database, Client Secret, mail credentials, dan token hanya di secret manager atau environment server.</li><li>Jangan commit file .env atau data pasien ke repository.</li><li>Batasi akses database, panel admin, media upload, log aplikasi, serta backup.</li><li>Produksi: APP_DEBUG=false, HTTPS, backup terenkripsi, dan rotasi kredensial mengikuti kebijakan organisasi.</li><li>Jika queue driver memakai database/Redis, jalankan worker yang dikelola process supervisor.</li></ul>
        <h3>Backup dan pemulihan</h3>
        <p>Backup dijalankan manual dengan <code>php artisan db:backup</code> dan disimpan di <b>storage/app/private/database-backups</b>. Tidak ada jadwal backup otomatis. File uploads berada di storage dan perlu dicadangkan terpisah. Instruksi manual, validasi arsip, dan pemulihan tersedia pada <b>docs/backup.md</b>.</p>
        <div class="callout"><b>Setelah perubahan konfigurasi:</b> jalankan migration yang ditinjau, bersihkan cache konfigurasi yang relevan, lalu verifikasi login, pembuatan appointment, EMR, transaksi, PDF, dan sync sandbox sesuai ruang lingkup perubahan.</div>
    </section>

    <section class="page">
        <h2>12 · Pemecahan Masalah</h2>
        <table><tr><th>Gejala</th><th>Pemeriksaan awal</th><th>Langkah penyelesaian</th></tr>
            <tr><td>Halaman login tidak terbuka</td><td>Alamat panel, status server, konfigurasi route/cache.</td><td>Buka root aplikasi (redirect ke /admin/login); cek log Laravel dan web server.</td></tr>
            <tr><td>Menu tidak terlihat</td><td>Role, permission Shield, hubungan user-klinik.</td><td>Minta admin memeriksa role dan permission; jangan menaikkan role tanpa persetujuan.</td></tr>
            <tr><td>Obat tidak muncul di resep</td><td>Klinik appointment, klinik pada obat, stok > 0, status tersedia.</td><td>Perbaiki mapping obat/klinik dan stok pada Drugs.</td></tr>
            <tr><td>Impor obat gagal</td><td>CSV UTF-8, delimiter titik koma, header, nama kategori.</td><td>Gunakan template hasil ekspor; buat kategori lebih dulu; cek pesan baris yang gagal.</td></tr>
            <tr><td>Nomor kunjungan tidak tampil</td><td>Appointment sudah disimpan dan nomor hasil generate.</td><td>Refresh record/daftar; cek log dan proses penyimpanan jika tetap kosong.</td></tr>
            <tr><td>EMR tidak menemukan pasien</td><td>Filter tanggal (default hari ini), ejaan nama, tanggal lahir, klinik akses.</td><td>Kosongkan tanggal untuk mencari lintas tanggal, isi nama/tanggal lahir lalu klik Cari.</td></tr>
            <tr><td>Tagihan belum ada</td><td>Status appointment belum Confirmed atau belum ada item billing.</td><td>Periksa tindakan/resep dan proses konfirmasi. Hindari mengonfirmasi ulang tanpa meninjau transaksi.</td></tr>
            <tr><td>SATUSEHAT sync gagal</td><td>Status pasien, NIK, IHS, Practitioner/Organization/Location, kode, credential, endpoint.</td><td>Baca error, cek log tanpa membocorkan secret, koreksi mapping dan uji ulang di sandbox.</td></tr>
            <tr><td>Logo tidak terunggah</td><td>Format gambar, ukuran, PHP upload limits, storage permission/link.</td><td>Gunakan JPG/PNG/WebP/AVIF sesuai batas aplikasi; cek storage dan log server.</td></tr>
        </table>
        <h3>Glosarium</h3>
        <table><tr><th>Istilah</th><th>Arti</th></tr>
            <tr><td>EMR</td><td>Electronic Medical Record, rekam medis elektronik per kunjungan.</td></tr>
            <tr><td>SOAP</td><td>Subjective, Objective, Assessment, Plan.</td></tr>
            <tr><td>Appointment</td><td>Jadwal/record kunjungan yang menghubungkan pasien, klinik, dan dokter.</td></tr>
            <tr><td>ICD-10</td><td>Klasifikasi diagnosis; kode perlu mengikuti katalog dan terminologi yang valid.</td></tr>
            <tr><td>IHS ID</td><td>Identifier pasien/tenaga kesehatan/fasilitas pada SATUSEHAT.</td></tr>
            <tr><td>FHIR</td><td>Standar pertukaran data kesehatan yang digunakan integrasi SATUSEHAT.</td></tr>
            <tr><td>Organization / Location / Practitioner</td><td>Fasilitas, lokasi pelayanan, dan tenaga kesehatan dalam referensi FHIR.</td></tr>
        </table>
        <div class="footer-note">SIMKLINIK · Nusahome Lab — Panduan pengguna dan administrator. Untuk perubahan perilaku fitur, rujuk catatan rilis/kode aplikasi terbaru.</div>
    </section>
</body>
</html>
