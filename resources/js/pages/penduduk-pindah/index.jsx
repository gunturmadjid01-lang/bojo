import { router } from '@inertiajs/react';
import MasterResourcePage from '@/components/master-resource-page';

export default function PendudukPindahIndex({ pendudukPindahs = [] }) {
    return (
        <MasterResourcePage
            title="Penduduk Keluar"
            group="Peristiwa Kependudukan"
            createHref="/penduduk-pindah/create"
            createLabel="Tambah Penduduk Keluar"
            data={pendudukPindahs}
            searchFields={['desa_tujuan', 'kabupaten_tujuan', 'nomor_surat_pindah']}
            searchPlaceholder="Cari penduduk keluar..."
            emptyMessage="Data penduduk keluar tidak ditemukan."
            itemLabel={(item) => `${item.penduduk?.nama_lengkap || 'Penduduk'} / ${item.tanggal_pindah || '-'}`}
            detailHref={(item) => `/penduduk-pindah/${item.id}`}
            editHref={(item) => `/penduduk-pindah/${item.id}/edit`}
            onDelete={(item) => window.confirm(`Hapus data pindah ${item.penduduk?.nama_lengkap || 'ini'}?`) && router.delete(`/penduduk-pindah/${item.id}`)}
            columns={[
                { name: 'Penduduk', selector: (item) => item.penduduk?.nama_lengkap || '-', sortable: true },
                { name: 'Tanggal Pindah', selector: (item) => item.tanggal_pindah || '-', sortable: true },
                { name: 'Tujuan', selector: (item) => [item.desa_tujuan, item.kabupaten_tujuan].filter(Boolean).join(', ') || item.alamat_tujuan || '-', sortable: true },
                { name: 'Status', selector: (item) => item.status || '-', sortable: true },
            ]}
        />
    );
}
