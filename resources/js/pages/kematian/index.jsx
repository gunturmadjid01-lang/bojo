import { router } from '@inertiajs/react';
import MasterResourcePage from '@/components/master-resource-page';

export default function KematianIndex({ kematians = [] }) {
    return (
        <MasterResourcePage
            title="Kematian"
            group="Peristiwa Kependudukan"
            createHref="/kematian/create"
            createLabel="Tambah Kematian"
            data={kematians}
            searchFields={['nomor_surat_kematian', 'tempat_meninggal', 'penyebab_meninggal']}
            searchPlaceholder="Cari kematian..."
            emptyMessage="Data kematian tidak ditemukan."
            itemLabel={(item) => `${item.penduduk?.nama_lengkap || 'Penduduk'} / ${item.tanggal_meninggal || '-'}`}
            detailHref={(item) => `/kematian/${item.id}`}
            editHref={(item) => `/kematian/${item.id}/edit`}
            onDelete={(item) => window.confirm(`Hapus kematian ${item.penduduk?.nama_lengkap || 'ini'}?`) && router.delete(`/kematian/${item.id}`)}
            columns={[
                { name: 'Penduduk', selector: (item) => item.penduduk?.nama_lengkap || '-', sortable: true },
                { name: 'Tanggal Meninggal', selector: (item) => item.tanggal_meninggal || '-', sortable: true },
                { name: 'Tempat', selector: (item) => item.tempat_meninggal || '-', sortable: true },
                { name: 'No. Surat', selector: (item) => item.nomor_surat_kematian || '-' },
            ]}
        />
    );
}
