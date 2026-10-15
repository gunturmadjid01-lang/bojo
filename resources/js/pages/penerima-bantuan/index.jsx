import MasterResourcePage from '@/components/master-resource-page';
import { router } from '@inertiajs/react';

const statusMeta = {
    menunggu_verifikasi: { label: 'Menunggu Verifikasi', className: 'bg-amber-100 text-amber-700' },
    proses_verifikasi: { label: 'Proses Verifikasi', className: 'bg-cyan-100 text-cyan-700' },
    layak: { label: 'Layak', className: 'bg-emerald-100 text-emerald-700' },
    tidak_layak: { label: 'Tidak Layak', className: 'bg-red-100 text-red-700' },
};

const formatDate = (value) => (value ? new Date(value).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : '-');

export default function Index({ penerimas = [] }) {
    return (
        <MasterResourcePage
            title="Penerima Bantuan"
            group="Bantuan Sosial"
            createHref="/penerima-bantuan/create"
            createLabel="Tambah Penerima"
            showStatistics={false}
            data={penerimas}
            searchFields={[
                (item) => item.penduduk?.nama_lengkap,
                (item) => item.penduduk?.nik,
                (item) => item.program?.nama,
                (item) => item.kartu_keluarga?.nomor_kk,
                (item) => statusMeta[item.status_alur]?.label,
            ]}
            columns={[
                { name: 'Program', selector: (item) => item.program?.nama || '-', sortable: true },
                { name: 'Penerima', selector: (item) => item.penduduk?.nama_lengkap || item.kartu_keluarga?.nomor_kk || '-', sortable: true },
                { name: 'Tanggal Terdaftar', selector: (item) => formatDate(item.tanggal_terdaftar), sortable: true },
                {
                    name: 'Status',
                    selector: (item) => statusMeta[item.status_alur]?.label || '-',
                    sortable: true,
                    cell: (item) => {
                        const status = statusMeta[item.status_alur] || statusMeta.menunggu_verifikasi;
                        return <span className={`rounded-full px-3 py-1 text-xs font-semibold ${status.className}`}>{status.label}</span>;
                    },
                },
            ]}
            detailHref={(item) => `/penerima-bantuan/${item.id}`}
            onDelete={(item) =>
                window.confirm(`Hapus penerima ${item.penduduk?.nama_lengkap || item.kartu_keluarga?.nomor_kk || ''}?`) &&
                router.delete(`/penerima-bantuan/${item.id}`)
            }
        />
    );
}
