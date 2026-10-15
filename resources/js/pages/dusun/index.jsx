import { router } from '@inertiajs/react';
import MasterResourcePage from '@/components/master-resource-page';

const Status = ({ active }) => <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>{active ? 'Aktif' : 'Nonaktif'}</span>;

export default function DusunIndex({ dusuns = [] }) {
    return <MasterResourcePage title="Master Dusun" group="Master Wilayah" createHref="/dusun/create" createLabel="Tambah Dusun" data={dusuns} searchFields={['nama', 'kode', (item) => item.desa?.nama_desa]} searchPlaceholder="Cari dusun..." emptyMessage="Data dusun tidak ditemukan." itemLabel={(item) => item.nama} detailHref={(item) => `/dusun/${item.id}`} editHref={(item) => `/dusun/${item.id}/edit`} onDelete={(item) => window.confirm(`Hapus dusun ${item.nama}?`) && router.delete(`/dusun/${item.id}`)} columns={[{ name: 'Nama', selector: (item) => item.nama, sortable: true }, { name: 'Kode', selector: (item) => item.kode || '-' }, { name: 'Desa', selector: (item) => item.desa?.nama_desa || '-' }, { name: 'Status', cell: (item) => <Status active={item.aktif} /> }]} />;
}
