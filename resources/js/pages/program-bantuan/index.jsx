import { router } from '@inertiajs/react';
import MasterResourcePage from '@/components/master-resource-page';

const Status = ({ value }) => <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${value === 'aktif' ? 'bg-emerald-50 text-emerald-700' : value === 'selesai' ? 'bg-blue-50 text-blue-700' : value === 'draft' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600'}`}>{value || 'Aktif'}</span>;

export default function ProgramBantuanIndex({ programBantuan = [] }) {
    return <MasterResourcePage title="Master Program Bantuan" createHref="/program-bantuan/create" createLabel="Tambah Program" data={programBantuan} searchFields={['kode', 'nama', 'sumber', 'status']} searchPlaceholder="Cari program..." emptyMessage="Data program bantuan tidak ditemukan." itemLabel={(item) => item.nama} detailHref={(item) => `/program-bantuan/${item.id}`} editHref={(item) => `/program-bantuan/${item.id}/edit`} onDelete={(item) => window.confirm(`Hapus program ${item.nama}?`) && router.delete(`/program-bantuan/${item.id}`)} columns={[{ name: 'Kode', selector: (item) => item.kode || '-', sortable: true }, { name: 'Nama', selector: (item) => item.nama, sortable: true }, { name: 'Sumber', selector: (item) => item.sumber || '-' }, { name: 'Tahun', selector: (item) => item.tahun || '-', sortable: true }, { name: 'Status', cell: (item) => <Status value={item.status} /> }]} />;
}
