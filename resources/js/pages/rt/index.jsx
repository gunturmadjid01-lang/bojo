import { router } from '@inertiajs/react';
import MasterResourcePage from '@/components/master-resource-page';

const Status = ({ active }) => <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>{active ? 'Aktif' : 'Nonaktif'}</span>;

export default function RTIndex({ rts = [] }) {
    return <MasterResourcePage title="Master RT" group="Master Wilayah" createHref="/rt/create" createLabel="Tambah RT" data={rts} searchFields={['nomor', (item) => item.rw?.nomor]} searchPlaceholder="Cari RT..." emptyMessage="Data RT tidak ditemukan." itemLabel={(item) => `RT ${item.nomor}`} detailHref={(item) => `/rt/${item.id}`} editHref={(item) => `/rt/${item.id}/edit`} onDelete={(item) => window.confirm(`Hapus RT ${item.nomor}?`) && router.delete(`/rt/${item.id}`)} columns={[{ name: 'RT', selector: (item) => `RT ${item.nomor}`, sortable: true }, { name: 'RW', selector: (item) => item.rw?.nomor ? `RW ${item.rw.nomor}` : '-' }, { name: 'Status', cell: (item) => <Status active={item.aktif} /> }]} />;
}
