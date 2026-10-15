import { router } from '@inertiajs/react';
import MasterResourcePage from '@/components/master-resource-page';

const Status = ({ active }) => <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>{active ? 'Aktif' : 'Nonaktif'}</span>;

export default function DisabilitasIndex({ disabilitas = [] }) {
    return <MasterResourcePage title="Master Jenis Disabilitas" createHref="/disabilitas/create" createLabel="Tambah Jenis" data={disabilitas} searchFields={['nama', 'keterangan']} searchPlaceholder="Cari jenis..." emptyMessage="Data jenis disabilitas tidak ditemukan." itemLabel={(item) => item.nama} detailHref={(item) => `/disabilitas/${item.id}`} editHref={(item) => `/disabilitas/${item.id}/edit`} onDelete={(item) => window.confirm(`Hapus jenis ${item.nama}?`) && router.delete(`/disabilitas/${item.id}`)} columns={[{ name: 'Nama', selector: (item) => item.nama, sortable: true }, { name: 'Keterangan', selector: (item) => item.keterangan || '-' }, { name: 'Status', cell: (item) => <Status active={item.aktif} /> }]} />;
}
