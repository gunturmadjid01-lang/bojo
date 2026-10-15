# Audit API Sistem Informasi Penduduk

## Status Implementasi

API menggunakan prefix `/api/v1` dan autentikasi bearer token Laravel Sanctum.

- `POST /api/v1/auth/login`: login dengan email atau username, password, dan `device_name`.
- `POST /api/v1/auth/logout`: mencabut token perangkat yang sedang digunakan.
- `GET /api/v1/auth/me`: profil minimal, permission, role, dan penugasan aktif.
- `GET /api/v1/dashboard?year=YYYY`: statistik yang hanya memuat modul yang diizinkan.
- `GET /api/v1/wilayah`: pilihan wilayah dan master pendidikan/pekerjaan sesuai cakupan user.
- `GET /api/v1/penduduk` dan `GET /api/v1/penduduk/{id}`: daftar/detail penduduk dengan pencarian dan pagination.
- `GET /api/v1/kartu-keluarga` dan `GET /api/v1/kartu-keluarga/{id}`: daftar/detail KK beserta anggota.
- `GET /api/v1/anggota-kartu-keluarga` dan `GET /api/v1/anggota-kartu-keluarga/{id}`: anggota KK dengan pencarian dan filter aktif.

Token dibatasi rate limit login dan API, memiliki masa berlaku default 30 hari, dan tidak mengirim password, remember token, NIK, atau data penduduk mentah pada endpoint autentikasi.

## Inventaris Fitur Web

Modul yang terdaftar di web dan harus memiliki authorization + cakupan wilayah yang sama saat dibuatkan endpoint:

- Master: desa, dusun, RW, RT, pendidikan, pekerjaan, jenis disabilitas.
- Kependudukan: kartu keluarga, anggota KK, penduduk, kelahiran, kematian, penduduk datang, penduduk pindah.
- Sosial: sosial ekonomi keluarga, sosial ekonomi penduduk, disabilitas penduduk.
- Bantuan: program bantuan, penerima bantuan, penyaluran bantuan.
- Perangkat desa: jabatan desa, penugasan perangkat.
- Administrasi: users, permissions, profile, password, dashboard.

## Temuan Keamanan

1. Sebelumnya belum ada route API, token guard, atau migration personal access token.
2. Seluruh data operasional wajib melewati `WilayahAccess`; global scope `HasWilayahScope` dan validasi write sudah menjadi boundary reusable.
3. API tidak boleh meneruskan controller Inertia secara langsung karena payload web dapat memuat PII dan bentuk response yang tidak stabil.
4. Endpoint CRUD berikutnya harus memakai Form Request/API Resource dan policy/module permission, serta menguji detail langsung, create, update, delete, dropdown, search, export, dan statistik lintas desa/dusun.
5. Token bersifat bearer; aplikasi Android wajib memakai HTTPS, menyimpan token di secure storage, dan tidak mencatat header Authorization.

## Rekomendasi Tahap Berikutnya

Implementasikan endpoint per kelompok modul, mulai dari penduduk/KK dan dropdown wilayah. Setiap endpoint harus menggunakan query model yang sudah memiliki scope wilayah, menyeleksi kolom secara eksplisit, memvalidasi foreign key melalui query scoped, dan memakai pagination dengan batas maksimum.

