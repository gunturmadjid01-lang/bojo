import { router } from '@inertiajs/react';
import MasterResourcePage from '@/components/master-resource-page';

const Status = ({ active }) => <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>{active ? 'Aktif' : 'Nonaktif'}</span>;

export default function RWIndex({ rws = [] }) {
    return <MasterResourcePage title="Master RW" group="Master Wilayah" createHref="/rw/create" createLabel="Tambah RW" data={rws} searchFields={['nomor', (item) => item.dusun?.nama]} searchPlaceholder="Cari RW..." emptyMessage="Data RW tidak ditemukan." itemLabel={(item) => `RW ${item.nomor}`} detailHref={(item) => `/rw/${item.id}`} editHref={(item) => `/rw/${item.id}/edit`} onDelete={(item) => window.confirm(`Hapus RW ${item.nomor}?`) && router.delete(`/rw/${item.id}`)} columns={[{ name: 'RW', selector: (item) => `RW ${item.nomor}`, sortable: true }, { name: 'Dusun', selector: (item) => item.dusun?.nama || '-' }, { name: 'Status', cell: (item) => <Status active={item.aktif} /> }]} />;
}
