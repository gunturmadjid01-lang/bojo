import { router } from '@inertiajs/react';
import MasterResourcePage from '@/components/master-resource-page';

export default function KelahiranIndex({ kelahirans = [] }) {
    return (
        <MasterResourcePage
            title="Kelahiran"
            group="Peristiwa Kependudukan"
            createHref="/kelahiran/create"
            createLabel="Tambah Kelahiran"
            data={kelahirans}
            searchFields={['nomor_akta_kelahiran', 'tempat_lahir', 'catatan']}
            searchPlaceholder="Cari kelahiran..."
            emptyMessage="Data kelahiran tidak ditemukan."
            itemLabel={(item) => `${item.penduduk?.nama_lengkap || 'Penduduk'} / ${item.tanggal_lahir || '-'}`}
            detailHref={(item) => `/kelahiran/${item.id}`}
            editHref={(item) => `/kelahiran/${item.id}/edit`}
            onDelete={(item) => window.confirm(`Hapus kelahiran ${item.penduduk?.nama_lengkap || 'ini'}?`) && router.delete(`/kelahiran/${item.id}`)}
            columns={[
                { name: 'Penduduk', selector: (item) => item.penduduk?.nama_lengkap || '-', sortable: true },
                { name: 'KK', selector: (item) => item.kartu_keluarga?.nomor_kk || '-', sortable: true },
                { name: 'Tempat / Tanggal Lahir', selector: (item) => [item.tempat_lahir, item.tanggal_lahir].filter(Boolean).join(', ') || '-', sortable: true },
                { name: 'No. Akta', selector: (item) => item.nomor_akta_kelahiran || '-' },
            ]}
        />
    );
}
