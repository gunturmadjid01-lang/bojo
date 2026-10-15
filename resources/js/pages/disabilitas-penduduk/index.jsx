import { router } from '@inertiajs/react';
import MasterResourcePage from '@/components/master-resource-page';

export default function DisabilitasPendudukIndex({ disabilitasPenduduks = [] }) {
    return (
        <MasterResourcePage
            title="Data Disabilitas"
            group="Data Sosial"
            createHref="/disabilitas-penduduk/create"
            createLabel="Tambah Data"
            showStatistics={false}
            data={disabilitasPenduduks}
            searchFields={['tingkat', 'keterangan']}
            searchPlaceholder="Cari data disabilitas..."
            emptyMessage="Data disabilitas penduduk tidak ditemukan."
            itemLabel={(item) => item.penduduk?.nama_lengkap || 'Penduduk'}
            detailHref={(item) => `/disabilitas-penduduk/${item.id}`}
            editHref={(item) => `/disabilitas-penduduk/${item.id}/edit`}
            onDelete={(item) => window.confirm(`Hapus data ${item.penduduk?.nama_lengkap || item.id}?`) && router.delete(`/disabilitas-penduduk/${item.id}`)}
            columns={[
                { name: 'Nama Penduduk', selector: (item) => item.penduduk?.nama_lengkap || '-', sortable: true },
                { name: 'NIK', selector: (item) => item.penduduk?.nik || '-', sortable: true },
                { name: 'Jenis Disabilitas', selector: (item) => item.jenis_disabilitas?.nama || '-', sortable: true },
                { name: 'Tingkat', selector: (item) => item.tingkat || '-', sortable: true },
                { name: 'Keterangan', selector: (item) => item.keterangan || '-', sortable: true },
            ]}
        />
    );
}
