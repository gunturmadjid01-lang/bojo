import { router } from '@inertiajs/react';
import MasterResourcePage from '@/components/master-resource-page';

export default function SosialEkonomiKeluargaIndex({ sosialEkonomiKeluargas = [] }) {
    return (
        <MasterResourcePage
            title="Sosial Ekonomi Keluarga"
            group="Data Sosial"
            createHref="/sosial-ekonomi-keluarga/create"
            createLabel="Tambah Data"
            showStatistics={false}
            data={sosialEkonomiKeluargas}
            searchFields={['status_kepemilikan_rumah', 'kondisi_rumah', 'kategori_ekonomi']}
            searchPlaceholder="Cari data sosial keluarga..."
            emptyMessage="Data sosial ekonomi keluarga tidak ditemukan."
            itemLabel={(item) => item.kartu_keluarga?.nomor_kk || 'Data keluarga'}
            detailHref={(item) => `/sosial-ekonomi-keluarga/${item.id}`}
            editHref={(item) => `/sosial-ekonomi-keluarga/${item.id}/edit`}
            onDelete={(item) => window.confirm(`Hapus data keluarga ${item.kartu_keluarga?.nomor_kk || item.id}?`) && router.delete(`/sosial-ekonomi-keluarga/${item.id}`)}
            columns={[
                { name: 'Nomor KK', selector: (item) => item.kartu_keluarga?.nomor_kk || '-', sortable: true },
                { name: 'Status Rumah', selector: (item) => item.status_kepemilikan_rumah || '-', sortable: true },
                { name: 'Kondisi Rumah', selector: (item) => item.kondisi_rumah || '-', sortable: true },
                { name: 'Kategori Ekonomi', selector: (item) => item.kategori_ekonomi || '-', sortable: true },
                { name: 'Tanggal Pendataan', selector: (item) => item.tanggal_pendataan || '-', sortable: true },
            ]}
        />
    );
}
