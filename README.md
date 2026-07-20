# Web Monitoring Receiving Goods

Aplikasi web untuk memonitor data **Monitoring Receiving Goods** (PT Karya Putra
Sangkuriang) berbasis **Laravel 11**, dijalankan di **XAMPP**.

Fitur:
- Filter data berdasarkan **rentang tanggal** (Tanggal Terima), customer, dan PIC.
- Kartu ringkasan:
  - **Jumlah Data BSTHP** (No. BSTHP unik)
  - **Item Diverifikasi PIC** (total + rincian per nama PIC, mis. "DANDI: 5")
  - **Code Item unik** (duplikat dihitung satu)
  - **Jumlah Barcode** (Label Barcode No, unik)
  - **Customer unik**
  - Total Qty & jumlah baris data
- Tabel detail data dengan pencarian & pagination.
- **Grafik tren** (Harian / Bulanan / Tahunan) untuk jumlah BSTHP, jumlah
  Customer, dan PIC Verifikator (item diverifikasi + jumlah PIC aktif),
  otomatis mengikuti filter tanggal/customer/PIC yang sedang dipakai.
- **Upload data** langsung dari halaman web (tidak perlu convert file manual) —
  cukup upload file export `.xls` (yang sebenarnya berformat HTML, sama seperti
  contoh yang Anda berikan) tiap kali ada data baru.
- Mode upload: **Tambah/Perbarui** (tidak menggandakan data jika file diupload
  ulang) atau **Ganti Semua Data**.

---

## 1. Persyaratan

- **XAMPP** dengan PHP **8.2 atau lebih baru** dan **MySQL/MariaDB** aktif.
  Cek versi PHP Anda: buka `xampp/php/php.exe -v` atau lihat di XAMPP Control Panel.
- **Composer** — https://getcomposer.org/download (installer Windows tersedia).

> Jika PHP bawaan XAMPP Anda masih versi 8.0/8.1, silakan update XAMPP ke versi
> terbaru (XAMPP 8.2.x) karena Laravel 11 membutuhkan PHP 8.2+.

---

## 2. Instalasi

1. **Salin folder project ini** ke dalam folder htdocs XAMPP, misalnya:
   ```
   C:\xampp\htdocs\receiving-goods-monitor
   ```

2. **Buka Command Prompt / Terminal** di folder tersebut, lalu install dependency:
   ```
   composer install
   ```

3. **Salin file environment**:
   ```
   copy .env.example .env
   ```
   (di macOS/Linux gunakan `cp .env.example .env`)

4. **Generate application key**:
   ```
   php artisan key:generate
   ```

5. **Buat database** melalui phpMyAdmin (`http://localhost/phpmyadmin`):
   - Buat database baru bernama **`receiving_goods_monitor`** (collation `utf8mb4_unicode_ci`).
   - Pastikan `.env` sudah sesuai (default XAMPP: user `root`, password kosong):
     ```
     DB_DATABASE=receiving_goods_monitor
     DB_USERNAME=root
     DB_PASSWORD=
     ```

6. **Jalankan migrasi** untuk membuat tabel:
   ```
   php artisan migrate
   ```

---

## 3. Menjalankan Aplikasi

Cara paling mudah (tidak perlu setting Virtual Host Apache):

```
php artisan serve
```

Lalu buka **http://127.0.0.1:8000** di browser.

**Alternatif** menjalankan lewat Apache XAMPP langsung: arahkan *Document Root*
Apache (atau buat Virtual Host baru) ke folder **`public/`** di dalam project ini
(bukan ke folder project itu sendiri), lalu restart Apache di XAMPP Control Panel.

---

## 4. Mengisi Data

Buka menu **"Upload Data"** di navbar, lalu upload file export dari sistem
(format `.xls` yang sebenarnya berupa HTML — sama seperti file yang Anda kirim).
Sudah disediakan contoh file di folder `contoh-data/` untuk uji coba pertama.

- Pilih mode **"Tambah/Perbarui"** untuk menambahkan data baru tanpa menghapus
  data lama (data dengan No. BSTHP + Label Barcode yang sama otomatis diperbarui,
  jadi aman diupload berkali-kali).
- Pilih **"Ganti Semua Data"** jika ingin mengganti seluruh isi database dengan
  file yang baru.

**Alternatif via terminal** (berguna untuk file besar atau otomatisasi):
```
php artisan import:receiving-goods "contoh-data/Receiving_Goods_Monitoring__32_.xls"
```
Tambahkan `--replace` di akhir perintah untuk mengganti semua data lama.

---

## 5. Struktur Data yang Terbaca

Importer membaca tabel `<tbody id="resultData">` pada file export dengan kolom:
No, Tanggal Terima, Tanggal BSTHP, No. BSTHP, Verify No, Verify By (PIC),
Code Item, Part Name, Part No, Model, Qty, Unit, BSTHP Barcode No,
Label Barcode No, Customer — persis seperti pada file `Receiving_Goods_Monitoring__32_.xls`
yang Anda berikan. Jika format export dari sistem sumber berubah struktur
kolomnya, sesuaikan urutan kolom di `app/Services/ReceivingGoodsImporter.php`.

---

## 6. Troubleshooting

- **Error "could not find driver"**: aktifkan extension `pdo_mysql` di
  `php.ini` (baris `extension=pdo_mysql` dihapus tanda `;` di depannya), lalu
  restart Apache/`php artisan serve`.
- **Halaman blank / 500 error**: jalankan `php artisan config:clear` dan cek
  log di `storage/logs/laravel.log`.
- **Upload gagal karena file besar**: naikkan `upload_max_filesize` dan
  `post_max_size` di `php.ini` XAMPP (misal jadi `64M`), lalu restart Apache.
- **Tabel kosong setelah upload**: pastikan file yang diupload memang hasil
  export "Monitoring Receiving Goods" dengan struktur tabel `id="resultData"`.
