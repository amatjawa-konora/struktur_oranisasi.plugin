# Plugin Struktur Organisasi

## Deskripsi

Plugin ini digunakan untuk menampilkan **struktur organisasi perpustakaan**. Sehingga memudahkan dalam proses desain dan perombakan struktur organisasi.

Tampilan akan otomatis menyesuaikan:

## Fitur

* Struktur organisasi berbentuk diagram
* Responsive (mobile friendly)
* Mendukung foto pegawai
* Bisa dimodifikasi sesuai kebutuhan

## Struktur Plugin
```bash
plugin/
│
├── images/
│   ├── blu.png
│   ├── logo.png
├── pages/
│   ├── struktur_organisasi.php #File Utama Struktur
│
└── struktur_organisasi.plugin.php #File Plugin
```

---
## Cara Install

### 1. Upload Plugin

* Upload folder `struktur_organisasi` ke:

```
direktori anda/plugins/
```

### 2. Aktifkan Plugin

* Masuk ke admin panel
* Aktifkan plugin **Struktur Organisasi**

---

## Cara Penggunaan
Edit bagian navbar dengan cara masuk ke Folder 
```bash
Direktori/template/tema_yang_digunakan/parts/_navbar.php
```
* Silahkan masuk ke folder template
* Buka tema yang anda gunakan
* Kemudian buka folder parts
* Kemudian buka file _navbar.php
* Kemudian silahkan buat menu baru dan masukan **http://localhost/nama_perpustakaan/index.php?p=struktur_organisasi** sebagai tujuan atau **https://nama_perpustakaan/index.php?p=struktur_organisasi** untuk yang online

---

## Struktur Layout

### Desktop (Diagram)

```
Direktur
|
Wakil Direktur
|
Kepala Perpustakaan
   |
   +------ Tata Usaha
   |
   +------ Bidang Sirkulasi
   |           |
   |           └── Tenaga Teknis
   |
   +------ Bidang Pelayanan
   |
   +------ Pengelolaan & Pengembangan
               |
               └── Teknis IT

Untuk bidang tambahan lainnya silahkan disesuaikan dengan kebutuhan institusi
```

---

## Customisasi

### Mengubah lebar card

```css
.org-staff {
    width: 260px;
}
```

### Mengubah warna utama

```css
.org-card {
    background: #0ea5a4;
}
```

### Mengubah lebar container

```css
.org-card-container {
    max-width: 1800px;
}
```
### Mengubah logo

Masuk ke folder
```bash
plugin/struktur_perpustakaan/images
```
Silahkan upload/tambahkan file foto di dalamnya.
kemudian masuk ke folder
```bash
plugin/struktur_perpustakaan/pages/struktur_organisasi.php
```
```html
<div class="org-header-logos">
  <img src="plugins/struktur_organisasi/images/logo.png" class="org-logo">
  <img src="plugins/struktur_organisasi/images/blu.png" class="org-logo">
</div>
```
ganti ke logo masing-masing institusi

---
### Mengubah foto struktur

Masuk ke folder
```bash
plugin/struktur_perpustakaan/images
```
Silahkan upload/tambahkan file foto anggota di dalamnya.
kemudian masuk ke folder
```bash
plugin/struktur_perpustakaan/pages/struktur_organisasi.php
```
```html
<img src="plugins/struktur_organisasi/images/direktur.png" class="org-photo">
```
ganti nama file **direktur.png** foto sesuai dengan nama file yang ada

### Mengubah Nama Struktur

Masuk ke folder
```bash
plugin/struktur_perpustakaan/pages/struktur_organisasi.php
```

```html
<!-- Direktur -->
<div class="org-level">
  <div class="org-card org-leader">
     <img src="plugins/struktur_organisasi/images/direktur.png" class="org-photo">
        <div class="org-info">
        <div class="org-role">DIREKTUR</div>
        <div class="org-name">Nama Direktur</div>
        <div class="org-nip">NIP: 00000000000000</div>
  </div>
</div>
Kemudian cari kode berikut. Lalu silahkan ubah nama-nama penjabat sesuai institusi masing-masing. Dan lakukan hal yang sama pada jabatan lainnya
```
---
## Catatan Penting

* Layout desktop menggunakan `position: absolute`
* Jika ukuran card diubah, posisi mungkin perlu disesuaikan
* Gunakan ukuran gambar yang konsisten (square) untuk hasil terbaik

---

## Lisensi

Free digunakan untuk kebutuhan perpustakaan
