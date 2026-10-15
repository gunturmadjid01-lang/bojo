import { router } from '@inertiajs/react';
import MasterResourcePage from '@/components/master-resource-page';

export default function PendudukDatangIndex({ pendudukDatangs = [] }) {
    return (
        <MasterResourcePage
            title="Penduduk Masuk"
            group="Peristiwa Kependudukan"
            createHref="/penduduk-datang/create"
            createLabel="Tambah Penduduk Masuk"
            data={pendudukDatangs}
            searchFields={['desa_sebelumnya', 'kabupaten_sebelumnya', 'nomor_surat_pindah']}
            searchPlaceholder="Cari penduduk masuk..."
            emptyMessage="Data penduduk masuk tidak ditemukan."
            itemLabel={(item) => `${item.penduduk?.nama_lengkap || 'Penduduk'} / ${item.tanggal_datang || '-'}`}
            detailHref={(item) => `/penduduk-datang/${item.id}`}
            editHref={(item) => `/penduduk-datang/${item.id}/edit`}
            onDelete={(item) => window.confirm(`Hapus data datang ${item.penduduk?.nama_lengkap || 'ini'}?`) && router.delete(`/penduduk-datang/${item.id}`)}
            columns={[
                { name: 'Penduduk', selector: (item) => item.penduduk?.nama_lengkap || '-', sortable: true },
                { name: 'Tanggal Datang', selector: (item) => item.tanggal_datang || '-', sortable: true },
                { name: 'Asal', selector: (item) => [item.desa_sebelumnya, item.kabupaten_sebelumnya].filter(Boolean).join(', ') || '-', sortable: true },
                { name: 'Status', selector: (item) => item.status || '-', sortable: true },
            ]}
        />
    );
}
