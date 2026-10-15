import { router } from '@inertiajs/react';
import MasterResourcePage from '@/components/master-resource-page';

export default function PendidikanIndex({ pendidikans = [] }) {
    return <MasterResourcePage title="Master Pendidikan" createHref="/pendidikan/create" createLabel="Tambah Pendidikan" data={pendidikans} searchFields={['kode', 'nama']} searchPlaceholder="Cari pendidikan..." emptyMessage="Data pendidikan tidak ditemukan." itemLabel={(item) => item.nama} detailHref={(item) => `/pendidikan/${item.id}`} editHref={(item) => `/pendidikan/${item.id}/edit`} onDelete={(item) => window.confirm(`Hapus pendidikan ${item.nama}?`) && router.delete(`/pendidikan/${item.id}`)} columns={[{ name: 'Kode', selector: (item) => item.kode || '-', sortable: true }, { name: 'Nama', selector: (item) => item.nama, sortable: true }]} />;
}
