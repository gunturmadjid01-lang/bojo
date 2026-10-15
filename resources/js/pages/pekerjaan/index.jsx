import { router } from '@inertiajs/react';
import MasterResourcePage from '@/components/master-resource-page';

export default function PekerjaanIndex({ pekerjaans = [] }) {
    return <MasterResourcePage title="Master Pekerjaan" createHref="/pekerjaan/create" createLabel="Tambah Pekerjaan" data={pekerjaans} searchFields={['kode', 'nama']} searchPlaceholder="Cari pekerjaan..." emptyMessage="Data pekerjaan tidak ditemukan." itemLabel={(item) => item.nama} detailHref={(item) => `/pekerjaan/${item.id}`} editHref={(item) => `/pekerjaan/${item.id}/edit`} onDelete={(item) => window.confirm(`Hapus pekerjaan ${item.nama}?`) && router.delete(`/pekerjaan/${item.id}`)} columns={[{ name: 'Kode', selector: (item) => item.kode || '-', sortable: true }, { name: 'Nama', selector: (item) => item.nama, sortable: true }]} />;
}
