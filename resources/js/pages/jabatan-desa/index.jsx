import { router } from '@inertiajs/react';
import MasterResourcePage from '@/components/master-resource-page';

const Status = ({ active }) => <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>{active ? 'Aktif' : 'Nonaktif'}</span>;

export default function JabatanDesaIndex({ jabatanDesas = [] }) {
    return <MasterResourcePage title="Master Jabatan Desa" createHref="/jabatan-desa/create" createLabel="Tambah Jabatan" data={jabatanDesas} searchFields={['kode', 'nama', 'tingkat']} searchPlaceholder="Cari jabatan..." emptyMessage="Data jabatan tidak ditemukan." itemLabel={(item) => item.nama} detailHref={(item) => `/jabatan-desa/${item.id}`} editHref={(item) => item.bawaan_sistem ? null : `/jabatan-desa/${item.id}/edit`} onDelete={(item) => item.bawaan_sistem ? null : (window.confirm(`Hapus jabatan ${item.nama}?`) && router.delete(`/jabatan-desa/${item.id}`))} columns={[{ name: 'Kode', selector: (item) => item.kode || '-', sortable: true }, { name: 'Nama', selector: (item) => item.nama, sortable: true }, { name: 'Tingkat', selector: (item) => item.tingkat || '-' }, { name: 'Status', cell: (item) => <Status active={item.aktif} /> }, { name: 'Sistem', cell: (item) => item.bawaan_sistem ? <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">Bawaan</span> : <span className="rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-medium text-cyan-700">Custom</span> }]} />;
}
