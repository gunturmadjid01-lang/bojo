import { router } from '@inertiajs/react';
import MasterResourcePage from '@/components/master-resource-page';

export default function SosialEkonomiPendudukIndex({ sosialEkonomiPenduduks = [] }) {
    return (
        <MasterResourcePage
            title="Sosial Ekonomi Penduduk"
            group="Data Sosial"
            createHref="/sosial-ekonomi-penduduk/create"
            createLabel="Tambah Data"
            showStatistics={false}
            data={sosialEkonomiPenduduks}
            searchFields={['status_pekerjaan', 'kategori_penghasilan', 'jaminan_kesehatan']}
            searchPlaceholder="Cari data sosial penduduk..."
            emptyMessage="Data sosial ekonomi penduduk tidak ditemukan."
            itemLabel={(item) => item.penduduk?.nama_lengkap || 'Penduduk'}
            detailHref={(item) => `/sosial-ekonomi-penduduk/${item.id}`}
            editHref={(item) => `/sosial-ekonomi-penduduk/${item.id}/edit`}
            onDelete={(item) => window.confirm(`Hapus data ${item.penduduk?.nama_lengkap || item.id}?`) && router.delete(`/sosial-ekonomi-penduduk/${item.id}`)}
            columns={[
                { name: 'Nama Penduduk', selector: (item) => item.penduduk?.nama_lengkap || '-', sortable: true },
                { name: 'NIK', selector: (item) => item.penduduk?.nik || '-', sortable: true },
                { name: 'Status Pekerjaan', selector: (item) => item.status_pekerjaan || '-', sortable: true },
                { name: 'Penghasilan', selector: (item) => item.penghasilan_bulanan ? `Rp ${Number(item.penghasilan_bulanan).toLocaleString('id-ID')}` : '-', sortable: true },
                { name: 'Jaminan', selector: (item) => item.jaminan_kesehatan || '-', sortable: true },
            ]}
        />
    );
}
