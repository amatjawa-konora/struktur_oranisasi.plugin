# Plugin Struktur Organisasi (v2.0)

Menampilkan bagan struktur organisasi perpustakaan di OPAC. Semua isi bagan
(judul, logo, jabatan, nama pejabat, NIP, foto, urutan) diatur dari panel admin,
tanpa perlu mengedit kode.

## Instalasi / Pembaruan dari v1

1. Unggah folder `file_name_slims/plugins` dengan isi plugin ini.
2. Aktifkan plugin **Struktur Organisasi** aktif di **Sistem → Plugin**.
3. Buka halaman `index.php?p=struktur_organisasi` atau menu admin
   **Keanggotaan → Struktur Organisasi**. Pada pembukaan pertama:
   - tabel `struktur_organisasi` & `struktur_organisasi_setting` dibuat otomatis,
   - data struktur lama (Direktur s.d. Teknis IT) beserta fotonya langsung diisikan.

Link di navbar OPAC tetap sama: `index.php?p=struktur_organisasi`.

## Mengatur dari Admin (Keanggotaan → Struktur Organisasi)

- **Judul & Logo**: ubah judul, sub-judul, warna utama, unggah/hapus logo kiri & kanan.
- **Daftar Jabatan**:
  - **Ubah**: ganti nama jabatan, nama pejabat, NIP, foto, atasan.
  - **+ Bawahan**: tambah jabatan langsung di bawah jabatan tersebut.
  - **↑ / ↓**: geser urutan kiri-kanan di antara jabatan dengan atasan yang sama.
  - **Hapus**: bawahannya tidak ikut terhapus, tetapi naik ke atasan berikutnya.
  - **Posisi "Staf samping"**: tampil di samping garis atasan (seperti Tata Usaha).
  - **Tampilkan di bagan**: hilangkan centang untuk menyembunyikan sementara.
- **Pratinjau**: bagan langsung terlihat di bawah daftar.

Foto: JPG/PNG/WEBP/GIF maks. 2 MB, sebaiknya potret/persegi.

## Lokasi file

- Foto & logo yang diunggah: `images/struktur_organisasi/`
  (di luar folder plugin, jadi aman saat plugin diperbarui).
- Folder `plugins/struktur_organisasi/images/` hanya dipakai sebagai data awal
  & gambar avatar default.

## Tampilan

- Desktop: bagan pohon dengan garis penghubung otomatis. Tidak lagi memakai
  posisi absolut, sehingga tetap rapi berapa pun jumlah jabatan dan panjang nama.
  Bila bagan lebih lebar dari layar, area bagan dapat digeser ke samping.
- HP (≤ 768px): daftar bertingkat ke bawah.

## Catatan

- Mengulang data awal: kosongkan tabel `struktur_organisasi`, hapus file
  `images/struktur_organisasi/.schema_version`, lalu buka halaman lagi.
