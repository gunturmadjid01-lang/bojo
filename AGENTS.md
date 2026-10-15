# Aturan Wajib Cakupan Akses Wilayah

## Sumber Kebenaran

- Spatie Laravel Permission menentukan **apa** yang boleh dilakukan user.
- Penugasan aktif menentukan **data wilayah mana** yang boleh diakses user.
- Jangan menentukan wilayah dari role atau menambahkan `desa_id`/`dusun_id` ke `users`.
- Alur wilayah wajib: `User -> perangkatDesa -> penugasan aktif -> jabatanDesa.tingkat -> desa_id/dusun_id`.

## Definisi Penugasan Aktif

Penugasan aktif berarti `status = aktif`, `tanggal_mulai <= hari ini`, dan (`tanggal_selesai IS NULL` atau `tanggal_selesai >= hari ini`). Gunakan satu scope/service terpusat; jangan menyalin kondisi ini ke controller.

## Cakupan

- `tingkat = desa`: seluruh data operasional dalam `desa_id` penugasan, semua dusun.
- `tingkat = dusun`: hanya data dalam `desa_id` dan `dusun_id` penugasan.
- User tanpa penugasan aktif tidak mendapat akses data operasional. Super Admin hanya mengikuti bypass yang sudah ada.
- Permission dan cakupan selalu diperiksa bersama.

## Wajib Diterapkan

Gunakan service/scope/policy reusable untuk list, detail/route binding, create, update, delete, restore, bulk action, dashboard/statistik, search/autocomplete, dropdown, export, laporan, API, dan job. Relasi dipakai bila tabel tidak memiliki `dusun_id` langsung (`whereHas` melalui penduduk/KK). Backend wajib memvalidasi/override wilayah dari penugasan; frontend bukan sumber keamanan.

Jangan memfilter berdasarkan nama jabatan atau role. Gunakan `jabatan_desa.tingkat` agar jabatan baru tetap kompatibel. Data master tanpa relasi wilayah tidak perlu difilter.

## Perubahan dan Pengujian

Pergantian pejabat harus menutup penugasan lama dan membuat record baru dalam satu transaksi; histori tidak dihapus. Setiap implementasi modul wilayah harus menguji akses Kepala Desa, Sekdes, Kepala Dusun, user tanpa penugasan, lintas desa/dusun, akses detail langsung, create/update/delete, permission, dashboard, dropdown, dan export. Sebelum selesai audit query (`all`, `find`, `count`, `sum`, `paginate`, `get`), N+1, policy, route, migration, dan fitur existing.


Semua select option atau react select yang ada baik select penduduk dan KK hanya tampil berdasarkan tugasnya
