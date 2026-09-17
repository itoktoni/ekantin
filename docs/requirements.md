# Dokumen Persyaratan

## Pendahuluan

Sistem e-Kanteen Sekolah adalah platform manajemen kanteen digital berbasis web yang memungkinkan siswa melakukan transaksi pembelian di kanteen menggunakan kartu prabayar berbarcode. Sistem ini mencakup manajemen saldo kartu siswa, distribusi pendapatan kepada vendor, pemotongan fee pengelola dan fee sistem secara otomatis, notifikasi transaksi kepada orang tua, pembatasan pengeluaran harian siswa, serta pelaporan dan ekspor data untuk seluruh pemangku kepentingan.

Sistem ini dirancang untuk digunakan oleh empat peran utama: Super Admin (pengelola keseluruhan sistem), Kasir Sekolah (melayani top up tunai), Vendor (mengelola produk dan melihat laporan toko sendiri), serta Orang Tua/Siswa (melakukan top up via web dan memantau transaksi).

## Glosarium

- **Sistem e-Kanteen**: Keseluruhan platform digital manajemen kanteen sekolah yang dibangun dalam proyek ini.
- **Super Admin**: Pengguna dengan hak akses tertinggi yang dapat mengelola seluruh konfigurasi sistem, pengguna, vendor, produk, laporan, dan parameter fee.
- **Kasir Sekolah**: Staf sekolah yang bertugas memproses top up saldo kartu siswa secara tunai.
- **Vendor**: Pemilik atau pengelola gerai/kios di kanteen sekolah yang memiliki akses untuk mengelola produk gerai sendiri dan melihat laporan penjualan gerainya.
- **Siswa**: Pengguna akhir yang melakukan pembelian di kanteen menggunakan kartu prabayar berbarcode.
- **Orang Tua**: Wali siswa yang dapat melakukan top up saldo kartu via web dashboard dan menerima notifikasi setiap transaksi yang dilakukan oleh siswa.
- **Kartu Siswa**: Kartu fisik prabayar yang memiliki barcode unik, digunakan sebagai alat pembayaran di kanteen.
- **Barcode Kartu**: Identifikasi unik berbentuk barcode yang tercetak pada kartu siswa, digunakan oleh kasir vendor untuk memverifikasi identitas dan saldo saat transaksi.
- **Saldo Kartu**: Nilai nominal uang digital yang tersimpan dalam akun kartu siswa di dalam Sistem e-Kanteen.
- **Top Up**: Proses penambahan saldo pada kartu siswa, baik melalui web dashboard maupun melalui kasir sekolah secara tunai.
- **Transaksi Pembelian**: Proses pembayaran yang terjadi ketika siswa membeli produk di gerai kanteen menggunakan saldo kartu.
- **Fee Pengelola**: Biaya yang dibebankan per transaksi untuk keperluan operasional sekolah, mencakup komponen kebersihan, keamanan, dan biaya pengelolaan. Besaran fee dapat dikonfigurasi oleh Super Admin.
- **Fee Sistem**: Biaya tetap per transaksi yang dikenakan untuk operasional platform Sistem e-Kanteen. Besaran fee dapat dikonfigurasi oleh Super Admin (contoh: Rp 500 per transaksi).
- **Distribusi Dana**: Proses penyaluran dana hasil transaksi kepada vendor setelah pemotongan fee pengelola dan fee sistem dilakukan secara otomatis.
- **Limit Pengeluaran Harian**: Batas maksimum total nominal transaksi yang dapat dilakukan oleh seorang siswa dalam satu hari kalender.
- **Notifikasi Transaksi**: Pesan pemberitahuan yang dikirimkan kepada orang tua melalui saluran yang tersedia setiap kali terjadi transaksi pembelian pada kartu siswa yang bersangkutan.
- **Laporan**: Ringkasan data transaksi, saldo, pendapatan, dan fee yang dapat diakses oleh pengguna sesuai dengan hak aksesnya.
- **Ekspor Laporan**: Fitur untuk mengunduh data laporan dalam format file (contoh: CSV, Excel, PDF).
- **Web Dashboard**: Antarmuka berbasis web yang digunakan oleh Orang Tua/Siswa, Vendor, Kasir Sekolah, dan Super Admin untuk mengakses fitur Sistem e-Kanteen.

---

## Persyaratan

### Persyaratan 1: Manajemen Kartu Siswa dan Barcode

**User Story:** Sebagai Super Admin, saya ingin mendaftarkan kartu prabayar berbarcode untuk setiap siswa, sehingga setiap siswa memiliki identitas unik yang dapat digunakan untuk bertransaksi di kanteen.

#### Kriteria Penerimaan

1. THE Sistem e-Kanteen SHALL mengasosiasikan setiap Barcode Kartu dengan satu akun Siswa secara unik.
2. WHEN Super Admin mendaftarkan kartu baru, THE Sistem e-Kanteen SHALL memvalidasi bahwa Barcode Kartu belum terdaftar dalam sistem sebelum menyimpan data kartu.
3. IF Barcode Kartu yang didaftarkan telah terdaftar pada akun Siswa lain, THEN THE Sistem e-Kanteen SHALL menolak pendaftaran dan menampilkan pesan kesalahan yang menyatakan barcode telah digunakan.
4. THE Sistem e-Kanteen SHALL menyimpan status kartu (aktif atau nonaktif) untuk setiap kartu siswa.
5. WHEN Super Admin menonaktifkan kartu siswa, THE Sistem e-Kanteen SHALL memperbarui status kartu menjadi nonaktif dan mencegah kartu tersebut digunakan untuk transaksi lebih lanjut.
6. WHILE status kartu Siswa adalah nonaktif, THE Sistem e-Kanteen SHALL menolak setiap upaya transaksi pembelian menggunakan kartu tersebut.

---

### Persyaratan 2: Top Up Saldo via Web Dashboard

**User Story:** Sebagai Orang Tua atau Siswa, saya ingin melakukan top up saldo kartu melalui web dashboard, sehingga saldo kartu dapat diisi ulang tanpa harus datang ke sekolah secara fisik.

#### Kriteria Penerimaan

1. THE Sistem e-Kanteen SHALL menyediakan fitur top up saldo kartu melalui Web Dashboard yang dapat diakses oleh Orang Tua dan Siswa.
2. WHEN Orang Tua atau Siswa mengajukan permintaan top up melalui Web Dashboard, THE Sistem e-Kanteen SHALL memvalidasi bahwa nominal top up memenuhi jumlah minimum yang telah ditetapkan oleh Super Admin.
3. IF nominal top up yang dimasukkan kurang dari jumlah minimum yang ditetapkan, THEN THE Sistem e-Kanteen SHALL menolak permintaan dan menampilkan pesan yang menyatakan jumlah minimum top up.
4. WHEN pembayaran top up melalui Web Dashboard berhasil dikonfirmasi oleh payment gateway, THE Sistem e-Kanteen SHALL menambahkan nominal top up ke Saldo Kartu Siswa dalam waktu tidak lebih dari 30 detik.
5. WHEN top up berhasil diproses, THE Sistem e-Kanteen SHALL menampilkan konfirmasi kepada pengguna yang mencakup nominal top up, saldo sebelumnya, dan saldo terkini.
6. THE Sistem e-Kanteen SHALL menyimpan riwayat setiap transaksi top up, mencakup tanggal, waktu, nominal, metode pembayaran, dan status transaksi.

---

### Persyaratan 3: Top Up Saldo via Kasir Sekolah (Tunai)

**User Story:** Sebagai Kasir Sekolah, saya ingin memproses top up saldo kartu siswa secara tunai, sehingga siswa atau orang tua yang tidak memiliki akses digital tetap dapat mengisi saldo kartu.

#### Kriteria Penerimaan

1. THE Sistem e-Kanteen SHALL menyediakan fitur top up tunai yang hanya dapat diakses oleh pengguna dengan peran Kasir Sekolah.
2. WHEN Kasir Sekolah memindai atau memasukkan Barcode Kartu Siswa, THE Sistem e-Kanteen SHALL menampilkan data Siswa yang bersangkutan beserta Saldo Kartu terkini.
3. IF Barcode Kartu yang dimasukkan tidak ditemukan dalam sistem, THEN THE Sistem e-Kanteen SHALL menampilkan pesan kesalahan kepada Kasir Sekolah yang menyatakan kartu tidak terditemukan.
4. WHEN Kasir Sekolah mengonfirmasi transaksi top up tunai, THE Sistem e-Kanteen SHALL menambahkan nominal top up ke Saldo Kartu Siswa dan mencatat transaksi top up beserta identitas Kasir Sekolah yang memproses.
5. THE Sistem e-Kanteen SHALL mencatat setiap transaksi top up tunai yang mencakup tanggal, waktu, nominal, identitas Kasir Sekolah yang memproses, dan identitas kartu Siswa yang dituju.

---

### Persyaratan 4: Transaksi Pembelian di Kanteen

**User Story:** Sebagai Siswa, saya ingin membayar pembelian di kanteen menggunakan barcode pada kartu saya, sehingga transaksi dapat dilakukan dengan cepat dan tanpa uang tunai.

#### Kriteria Penerimaan

1. WHEN kasir gerai vendor memindai Barcode Kartu Siswa, THE Sistem e-Kanteen SHALL menampilkan data Siswa, Saldo Kartu terkini, dan daftar produk gerai vendor yang bersangkutan.
2. WHEN kasir gerai vendor mengonfirmasi transaksi pembelian, THE Sistem e-Kanteen SHALL memvalidasi bahwa Saldo Kartu Siswa mencukupi untuk membayar total nominal transaksi beserta Fee Pengelola dan Fee Sistem.
3. IF Saldo Kartu Siswa tidak mencukupi untuk membayar total nominal transaksi beserta seluruh fee, THEN THE Sistem e-Kanteen SHALL menolak transaksi dan menampilkan pesan kepada kasir gerai vendor yang menyatakan saldo tidak mencukupi.
4. WHEN transaksi pembelian berhasil diproses, THE Sistem e-Kanteen SHALL mengurangi Saldo Kartu Siswa sebesar total nominal transaksi ditambah Fee Pengelola dan Fee Sistem secara atomik dalam satu operasi database.
5. WHEN transaksi pembelian berhasil diproses, THE Sistem e-Kanteen SHALL mencatat detail transaksi yang mencakup tanggal, waktu, identitas Siswa, identitas gerai Vendor, daftar produk yang dibeli beserta harga satuan dan kuantitas, total pembelian, nominal Fee Pengelola, nominal Fee Sistem, dan saldo akhir Siswa.
6. THE Sistem e-Kanteen SHALL menyelesaikan proses validasi saldo dan pencatatan transaksi dalam waktu tidak lebih dari 5 detik sejak kasir gerai vendor mengonfirmasi transaksi.

---

### Persyaratan 5: Pemotongan Fee dan Distribusi Dana

**User Story:** Sebagai Super Admin, saya ingin fee pengelola dan fee sistem dipotong otomatis dari setiap transaksi sebelum dana masuk ke vendor, sehingga pendapatan sekolah dan operasional platform terjamin tanpa proses manual.

#### Kriteria Penerimaan

1. THE Sistem e-Kanteen SHALL memotong Fee Pengelola dan Fee Sistem dari Saldo Kartu Siswa secara otomatis pada setiap transaksi pembelian yang berhasil, sebelum dana dialokasikan ke saldo Vendor.
2. THE Sistem e-Kanteen SHALL menghitung Fee Pengelola dan Fee Sistem berdasarkan parameter yang dikonfigurasi oleh Super Admin pada saat transaksi diproses.
3. WHEN transaksi pembelian berhasil diproses, THE Sistem e-Kanteen SHALL mengalokasikan dana ke saldo Vendor sebesar total nominal pembelian produk dikurangi Fee Pengelola dan Fee Sistem.
4. THE Sistem e-Kanteen SHALL menyimpan rincian pemotongan fee untuk setiap transaksi, mencakup nominal Fee Pengelola, nominal Fee Sistem, dan dana bersih yang dialokasikan kepada Vendor.
5. WHEN Super Admin mengubah nilai Fee Sistem atau komponen Fee Pengelola, THE Sistem e-Kanteen SHALL menerapkan nilai fee yang baru hanya pada transaksi yang terjadi setelah perubahan disimpan, tanpa mempengaruhi riwayat transaksi sebelumnya.

---

### Persyaratan 6: Konfigurasi Fee oleh Super Admin

**User Story:** Sebagai Super Admin, saya ingin mengonfigurasi besaran fee sistem dan fee pengelola secara fleksibel, sehingga nilai fee dapat disesuaikan dengan kebijakan operasional sekolah tanpa perlu mengubah kode program.

#### Kriteria Penerimaan

1. THE Sistem e-Kanteen SHALL menyediakan antarmuka konfigurasi fee yang hanya dapat diakses oleh Super Admin.
2. THE Sistem e-Kanteen SHALL memungkinkan Super Admin untuk mengatur nilai Fee Sistem dalam satuan rupiah per transaksi.
3. THE Sistem e-Kanteen SHALL memungkinkan Super Admin untuk mengatur komponen Fee Pengelola, mencakup setidaknya komponen kebersihan, keamanan, dan biaya pengelolaan umum, masing-masing dalam satuan rupiah per transaksi.
4. WHEN Super Admin menyimpan konfigurasi fee baru, THE Sistem e-Kanteen SHALL memvalidasi bahwa semua nilai fee adalah bilangan bulat positif atau nol.
5. IF nilai fee yang dimasukkan Super Admin bukan bilangan bulat positif atau nol, THEN THE Sistem e-Kanteen SHALL menolak penyimpanan dan menampilkan pesan kesalahan yang menjelaskan format nilai fee yang valid.
6. THE Sistem e-Kanteen SHALL menyimpan riwayat perubahan konfigurasi fee, mencakup nilai lama, nilai baru, tanggal perubahan, dan identitas Super Admin yang melakukan perubahan.

---

### Persyaratan 7: Limit Pengeluaran Harian Siswa

**User Story:** Sebagai Orang Tua, saya ingin menetapkan batas maksimum pengeluaran harian untuk kartu siswa saya, sehingga saya dapat mengontrol dan membatasi total belanja anak di kanteen setiap harinya.

#### Kriteria Penerimaan

1. THE Sistem e-Kanteen SHALL menyediakan fitur pengaturan Limit Pengeluaran Harian yang dapat diakses oleh Orang Tua dan Super Admin melalui Web Dashboard.
2. WHEN Orang Tua atau Super Admin menetapkan Limit Pengeluaran Harian untuk kartu Siswa, THE Sistem e-Kanteen SHALL menyimpan nilai limit dalam satuan rupiah per hari kalender.
3. WHILE Limit Pengeluaran Harian aktif pada kartu Siswa, THE Sistem e-Kanteen SHALL memvalidasi bahwa total transaksi pembelian Siswa pada hari kalender yang sama belum melampaui nilai limit sebelum menyetujui transaksi baru.
4. IF total transaksi pembelian Siswa pada hari kalender yang sama akan melampaui Limit Pengeluaran Harian apabila transaksi baru disetujui, THEN THE Sistem e-Kanteen SHALL menolak transaksi tersebut dan menampilkan pesan yang menyatakan limit harian telah tercapai beserta sisa limit yang tersedia.
5. THE Sistem e-Kanteen SHALL mereset akumulasi pengeluaran harian setiap Siswa pada pukul 00:00 waktu setempat setiap hari.
6. WHERE Limit Pengeluaran Harian tidak ditetapkan untuk suatu kartu Siswa, THE Sistem e-Kanteen SHALL tidak membatasi jumlah pengeluaran harian kartu tersebut.

---

### Persyaratan 8: Notifikasi Transaksi kepada Orang Tua

**User Story:** Sebagai Orang Tua, saya ingin menerima notifikasi setiap kali kartu siswa saya digunakan untuk bertransaksi, sehingga saya dapat memantau aktivitas belanja anak secara real-time.

#### Kriteria Penerimaan

1. WHEN transaksi pembelian pada kartu Siswa berhasil diproses, THE Sistem e-Kanteen SHALL mengirimkan Notifikasi Transaksi kepada Orang Tua dari Siswa yang bersangkutan dalam waktu tidak lebih dari 60 detik setelah transaksi dikonfirmasi.
2. THE Sistem e-Kanteen SHALL menyertakan informasi berikut dalam setiap Notifikasi Transaksi: nama Siswa, nama gerai Vendor, daftar produk yang dibeli, total nominal transaksi, dan Saldo Kartu terkini setelah transaksi.
3. WHEN top up saldo berhasil diproses pada kartu Siswa, THE Sistem e-Kanteen SHALL mengirimkan notifikasi kepada Orang Tua yang mencakup nominal top up dan Saldo Kartu terkini.
4. IF pengiriman Notifikasi Transaksi gagal pada percobaan pertama, THEN THE Sistem e-Kanteen SHALL melakukan percobaan pengiriman ulang sebanyak maksimal 3 kali dengan jeda antar percobaan tidak kurang dari 30 detik.
5. THE Sistem e-Kanteen SHALL menyimpan log pengiriman notifikasi untuk setiap transaksi, mencakup status pengiriman, waktu percobaan, dan saluran notifikasi yang digunakan.

---

### Persyaratan 9: Manajemen Produk oleh Vendor

**User Story:** Sebagai Vendor, saya ingin mengelola produk yang tersedia di gerai saya melalui Web Dashboard, sehingga daftar menu dan harga yang ditampilkan kepada siswa selalu akurat dan terkini.

#### Kriteria Penerimaan

1. THE Sistem e-Kanteen SHALL membatasi akses manajemen produk setiap Vendor hanya pada produk yang terdaftar di bawah gerai milik Vendor tersebut.
2. THE Sistem e-Kanteen SHALL menyediakan fitur penambahan produk baru yang mencakup nama produk, harga satuan dalam satuan rupiah, dan status ketersediaan produk (tersedia atau tidak tersedia).
3. WHEN Vendor menambahkan produk baru, THE Sistem e-Kanteen SHALL memvalidasi bahwa nama produk tidak kosong dan harga satuan adalah bilangan bulat positif.
4. IF nama produk kosong atau harga satuan bukan bilangan bulat positif saat penambahan produk, THEN THE Sistem e-Kanteen SHALL menolak penyimpanan dan menampilkan pesan kesalahan yang merinci field yang tidak valid.
5. WHEN Vendor memperbarui harga produk, THE Sistem e-Kanteen SHALL menerapkan harga baru pada transaksi yang terjadi setelah perubahan disimpan, tanpa mempengaruhi riwayat transaksi sebelumnya.
6. WHEN Vendor mengubah status produk menjadi tidak tersedia, THE Sistem e-Kanteen SHALL menyembunyikan produk tersebut dari daftar produk yang ditampilkan kepada Siswa pada saat transaksi.

---

### Persyaratan 10: Laporan dan Ekspor Data

**User Story:** Sebagai Super Admin, saya ingin mengakses laporan detail seluruh transaksi, fee, dan saldo, serta mengekspornya ke dalam format file, sehingga data keuangan dan operasional kanteen dapat dianalisis dan diarsipkan dengan mudah.

#### Kriteria Penerimaan

1. THE Sistem e-Kanteen SHALL menyediakan laporan transaksi yang dapat difilter berdasarkan rentang tanggal, identitas Siswa, identitas Vendor, dan status transaksi, yang hanya dapat diakses oleh Super Admin.
2. THE Sistem e-Kanteen SHALL menyediakan laporan pendapatan Vendor yang menampilkan total penjualan, total Fee Pengelola yang dipotong, total Fee Sistem yang dipotong, dan dana bersih yang diterima Vendor untuk setiap gerai dalam periode waktu yang dipilih.
3. THE Sistem e-Kanteen SHALL menyediakan laporan saldo kartu yang menampilkan riwayat mutasi saldo setiap kartu Siswa, yang hanya dapat diakses oleh Super Admin.
4. WHEN Super Admin mengakses halaman laporan, THE Sistem e-Kanteen SHALL menampilkan data laporan sesuai dengan filter yang diterapkan dalam waktu tidak lebih dari 10 detik.
5. THE Sistem e-Kanteen SHALL menyediakan fitur ekspor laporan dalam setidaknya dua format file: CSV dan Excel (.xlsx).
6. WHEN Super Admin mengajukan permintaan ekspor laporan, THE Sistem e-Kanteen SHALL menghasilkan file ekspor yang mencakup seluruh data sesuai filter yang aktif dan menyediakan tautan unduhan dalam waktu tidak lebih dari 30 detik.
7. THE Sistem e-Kanteen SHALL membatasi akses laporan Vendor hanya pada data transaksi dan pendapatan gerai milik Vendor yang bersangkutan.
8. WHEN Vendor mengakses laporan gerainya, THE Sistem e-Kanteen SHALL menampilkan ringkasan penjualan, daftar transaksi, dan pendapatan bersih gerai tersebut tanpa menampilkan data fee sistem atau data gerai vendor lain.

---

### Persyaratan 11: Manajemen Pengguna dan Peran oleh Super Admin

**User Story:** Sebagai Super Admin, saya ingin mengelola semua akun pengguna dalam sistem, sehingga hak akses setiap pengguna dapat dikontrol sesuai dengan peran dan tanggung jawabnya.

#### Kriteria Penerimaan

1. THE Sistem e-Kanteen SHALL membatasi akses manajemen pengguna (tambah, ubah, nonaktifkan akun) hanya untuk pengguna dengan peran Super Admin.
2. THE Sistem e-Kanteen SHALL mendukung empat peran pengguna: Super Admin, Kasir Sekolah, Vendor, dan Orang Tua/Siswa.
3. WHEN Super Admin mendaftarkan akun Vendor baru, THE Sistem e-Kanteen SHALL mengasosiasikan akun Vendor tersebut dengan satu atau lebih gerai yang telah terdaftar dalam sistem.
4. WHEN Super Admin menonaktifkan akun pengguna, THE Sistem e-Kanteen SHALL mencegah akun tersebut dari melakukan login ke Sistem e-Kanteen.
5. IF pengguna dengan akun nonaktif mencoba login, THEN THE Sistem e-Kanteen SHALL menolak login dan menampilkan pesan yang menyatakan akun tidak aktif.
6. THE Sistem e-Kanteen SHALL menyimpan log audit untuk setiap perubahan data akun pengguna, mencakup jenis perubahan, tanggal dan waktu perubahan, serta identitas Super Admin yang melakukan perubahan.

---

### Persyaratan 12: Keamanan Akses dan Autentikasi

**User Story:** Sebagai Super Admin, saya ingin memastikan bahwa setiap pengguna hanya dapat mengakses fitur yang sesuai dengan perannya, sehingga integritas data keuangan dan privasi informasi siswa terjaga.

#### Kriteria Penerimaan

1. THE Sistem e-Kanteen SHALL mewajibkan autentikasi berbasis kredensial (nama pengguna dan kata sandi) untuk semua pengguna sebelum mengizinkan akses ke fitur sistem.
2. THE Sistem e-Kanteen SHALL menerapkan kontrol akses berbasis peran, sehingga setiap pengguna hanya dapat mengakses fitur dan data yang diizinkan sesuai dengan perannya.
3. WHEN pengguna gagal melakukan login sebanyak 5 kali berturut-turut dengan kredensial yang salah, THE Sistem e-Kanteen SHALL mengunci akun pengguna tersebut selama 15 menit.
4. IF akun pengguna terkunci karena percobaan login yang gagal, THEN THE Sistem e-Kanteen SHALL menampilkan pesan yang menyatakan akun terkunci beserta estimasi waktu pembukaannya.
5. THE Sistem e-Kanteen SHALL mengakhiri sesi pengguna secara otomatis setelah 30 menit tidak ada aktivitas pada sesi tersebut.
6. THE Sistem e-Kanteen SHALL menyimpan seluruh data sensitif, termasuk kata sandi pengguna, dalam bentuk terenkripsi menggunakan algoritma hashing yang aman.
