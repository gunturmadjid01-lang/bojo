import { router } from '@inertiajs/react';
import MasterResourcePage from '@/components/master-resource-page';

export default function KartuKeluargaIndex({ kartuKeluargas = [] }) {
    return (
        <MasterResourcePage
            title="Kartu Keluarga"
            group="Kependudukan"
            createHref="/kartu-keluarga/create"
            createLabel="Tambah Kartu Keluarga"
            data={kartuKeluargas}
            searchFields={['nomor_kk', 'alamat', 'status']}
            searchPlaceholder="Cari kartu keluarga..."
            emptyMessage="Data kartu keluarga tidak ditemukan."
            itemLabel={(item) => item.nomor_kk}
            detailHref={(item) => `/kartu-keluarga/${item.id}`}
            editHref={(item) => `/kartu-keluarga/${item.id}/edit`}
            onDelete={(item) => window.confirm(`Hapus kartu keluarga ${item.nomor_kk}?`) && router.delete(`/kartu-keluarga/${item.id}`)}
            columns={[
                { name: 'Nomor KK', selector: (item) => item.nomor_kk || '-', sortable: true },
                { name: 'Kepala Keluarga', selector: (item) => item.kepala_keluarga?.penduduk?.nama_lengkap || '-', sortable: true },
                { name: 'Desa', selector: (item) => item.desa?.nama_desa || '-', sortable: true },
                { name: 'Dusun', selector: (item) => item.dusun?.nama || '-', sortable: true },
                { name: 'RW', selector: (item) => item.rw?.nomor ? `RW ${item.rw.nomor}` : '-', sortable: true },
                { name: 'RT', selector: (item) => item.rt?.nomor ? `RT ${item.rt.nomor}` : '-', sortable: true },
                { name: 'Status', selector: (item) => item.status || '-', sortable: true },
            ]}
        />
    );
}
