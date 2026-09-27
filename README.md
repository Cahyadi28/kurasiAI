# KurasiAI

Kode pendamping buku **"Dari Fungsi ke Objek: Membangun Aplikasi Kurasi Konten AI dengan OOP PHP 8.1+"**
karya Cahyadi.

KurasiAI adalah aplikasi web kecil untuk mengelola konten buatan AI: AI membuat draf, Penulis merevisi dan
mengirimnya ke review, Editor menyetujui atau menolak, lalu Penulis menerbitkannya.
*AI yang membuat, manusia yang mengelola, dan OOP yang merapikan.*

Repository ini hanya berisi kode. Penjelasan setiap langkah ada di buku.

## Tag per bab

Keadaan kode di akhir setiap bab ditandai dengan tag:

| Tag | Bab |
|---|---|
| `bab-01` | Bab 1: Saat Kode Prosedural Mulai Kewalahan |
| `bab-02` | Bab 2: Class dan Object Pertama |
| `bab-03` | Bab 3: Enkapsulasi: Menjaga Data Tetap Sah |
| `bab-04` | Bab 4: Enum dan Match: Siklus Hidup Draf |
| `bab-05` | Bab 5: Inheritance dan Abstract Class: Ragam Konten |
| `bab-06` | Bab 6: Interface dan Polimorfisme: Mengganti Mesin AI |
| `bab-07` | Bab 7: Static, Konstanta, dan Trait |
| `bab-08` | Bab 8: Komposisi dan Dependency Injection |
| `bab-09` | Bab 9: Namespace dan Autoloading dengan Composer |
| `bab-10` | Bab 10: Exception: Saat Ada yang Salah |
| `bab-11` | Bab 11: Waktu dan Riwayat: DateTimeImmutable |
| `bab-12` | Bab 12: Menyimpan Data: PDO, SQLite, dan `.env` |
| `bab-13` | Bab 13: Repository: Menjembatani Objek dan Database |
| `bab-14` | Bab 14: Kembali ke Web: Form, Validasi dengan Attribute, dan CSRF |
| `bab-15` | Bab 15: Login, Sesi, dan Hak Akses |
| `bab-16` | Bab 16: Satu Pintu Masuk: Front Controller, Router, dan Controller |
| `bab-bonus` | Bab Bonus: Menyambung ke AI Sungguhan |

Cabang `main` berisi keadaan akhir, sama dengan `bab-bonus`.

Untuk melihat kode di akhir sebuah bab:

```
git checkout bab-05
```

Untuk membandingkan kode Anda sendiri dengan kode di tag tersebut, jalankan perintah ini dari folder proyek
Anda (dengan repository ini di folder `../kurasiai-kode`):

```
git diff --no-index ../kurasiai-kode/src src
```

> Bab 2–8 memuat class dengan `require_once` lewat `bootstrap.php`, sebelum Composer autoload diperkenalkan
> di Bab 9. Skrip `demo/bab-XX.php` hanya dijamin berjalan pada tag bab-nya sendiri.

## Kebutuhan

- PHP 8.1 atau lebih baru (dianjurkan 8.4) dengan ekstensi `curl`, `mbstring`, `openssl`, `pdo_sqlite`,
  `sqlite3`, dan `zip`
- Composer 2

## Menjalankan versi akhir

Pasang alat bantu:

```
composer install
```

Salin pengaturan, lalu buka `.env` dan isi `SEED_PASSWORD` (minimal 8 karakter):

```
cp .env.example .env
```

Buat database dan data awal:

```
php bin/setup-db.php
```

```
php bin/seed.php
```

Jalankan server:

```
php -S localhost:8000 -t public public/index.php
```

Buka `http://localhost:8000` dan masuk dengan salah satu akun contoh: `rina@kurasiai.test` atau
`sari@kurasiai.test` (Penulis), atau `budi@kurasiai.test` (Editor). Passwordnya adalah `SEED_PASSWORD` yang
Anda isi di `.env`.

Secara bawaan KurasiAI memakai AI tiruan (`AI_PROVIDER=fake`), jadi tidak butuh API key. Cara menyambung ke
layanan AI sungguhan dijelaskan di Bab Bonus.

> Jangan pernah meng-commit berkas `.env`. Berkas itu sudah tercantum di `.gitignore`.

## Tes dan pemeriksaan kode

```
composer test
```

```
composer analyse
```

## Lisensi

Kode di repository ini dirilis dengan [lisensi MIT](LICENSE). Teks buku tidak termasuk dalam lisensi ini.
