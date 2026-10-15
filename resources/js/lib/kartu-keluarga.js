export function namaKepalaKeluarga(kartuKeluarga) {
    const langsung = kartuKeluarga?.kepala_keluarga?.penduduk?.nama_lengkap || kartuKeluarga?.kepalaKeluarga?.penduduk?.nama_lengkap;
    if (langsung) return langsung;
    const anggota = kartuKeluarga?.anggota_keluarga || kartuKeluarga?.anggotaKeluarga || kartuKeluarga?.anggota || [];
    return anggota.find((item) => item.aktif !== false && item.hubungan_keluarga === 'Kepala Keluarga')?.penduduk?.nama_lengkap || 'Belum ada kepala keluarga';
}

export function labelKartuKeluarga(kartuKeluarga) {
    return [kartuKeluarga?.nomor_kk || '-', namaKepalaKeluarga(kartuKeluarga), kartuKeluarga?.alamat || 'Alamat belum diisi'].join(' — ');
}
