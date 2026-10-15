import { router } from '@inertiajs/react';
import MasterResourcePage from '@/components/master-resource-page';

export default function AnggotaKartuKeluargaIndex({ anggotaKartuKeluargas = [] }) {
    return (
        <MasterResourcePage
            title="Anggota Keluarga"
            group="Kependudukan"
            createHref="/anggota-kartu-keluarga/create"
            createLabel="Tambah Anggota Keluarga"
            showStatistics={false}
            data={anggotaKartuKeluargas}
            searchFields={['hubungan_keluarga', 'alasan_keluar']}
            searchPlaceholder="Cari anggota keluarga..."
            emptyMessage="Data anggota keluarga tidak ditemukan."
            itemLabel={(item) => `${item.penduduk?.nama_lengkap || '-'} / ${item.kartu_keluarga?.nomor_kk || '-'}`}
            detailHref={(item) => `/anggota-kartu-keluarga/${item.id}`}
            editHref={(item) => `/anggota-kartu-keluarga/${item.id}/edit`}
            onDelete={(item) => window.confirm(`Hapus anggota keluarga ${item.penduduk?.nama_lengkap || 'ini'}?`) && router.delete(`/anggota-kartu-keluarga/${item.id}`)}
            columns={[
                { name: 'KK', selector: (item) => item.kartu_keluarga?.nomor_kk || '-', sortable: true },
                { name: 'Penduduk', selector: (item) => `${item.penduduk?.nama_lengkap || '-'} (${item.penduduk?.nik || '-'})`, sortable: true },
                { name: 'Hubungan', selector: (item) => item.hubungan_keluarga || '-', sortable: true },
                { name: 'Status', selector: (item) => item.aktif ? 'Aktif' : 'Tidak Aktif', sortable: true },
            ]}
        />
    );
}
