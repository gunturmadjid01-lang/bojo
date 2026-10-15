import { Head, Link, usePage } from '@inertiajs/react';
import { Plus, RefreshCw, SquareX } from 'lucide-react';
import MasterResourcePage from '@/components/master-resource-page';
import { canAccessHref, hasPermissionName } from '@/lib/permissions';

const formatTanggal = (value) => {
    if (!value) return '-';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }).format(date);
};

const Status = ({ value }) => <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${value === 'aktif' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>{value === 'aktif' ? 'Aktif' : value || 'Belum Ada'}</span>;

export default function PenugasanIndex({ assignments = [] }) {
    const user = usePage().props?.auth?.user;
    const canView = canAccessHref(user, '/penugasan-perangkat', 'view') || hasPermissionName(user, 'lihat-penugasan-perangkat');
    const canCreate = canAccessHref(user, '/penugasan-perangkat/create', 'create') || hasPermissionName(user, 'tambah-penugasan-perangkat');
    const canReplace = canAccessHref(user, '/penugasan-perangkat', 'edit') || hasPermissionName(user, 'ganti-penugasan-perangkat');
    const canEnd = canAccessHref(user, '/penugasan-perangkat', 'edit') || hasPermissionName(user, 'akhiri-penugasan-perangkat');
    const actions = (item) => <div className="flex flex-wrap justify-end gap-1.5">{canView && <Link href={`/penugasan-perangkat/${item.id}`} className="rounded-md bg-cyan-500 px-2.5 py-1.5 text-xs font-semibold text-white">Detail</Link>}{canReplace && <Link href={`/penugasan-perangkat/${item.id}/ganti`} className="inline-flex items-center gap-1 rounded-md bg-emerald-600 px-2.5 py-1.5 text-xs font-semibold text-white"><RefreshCw className="size-3.5" />Ganti</Link>}{canEnd && <Link href={`/penugasan-perangkat/${item.id}/akhiri`} className="inline-flex items-center gap-1 rounded-md bg-red-500 px-2.5 py-1.5 text-xs font-semibold text-white"><SquareX className="size-3.5" />Akhiri</Link>}</div>;
    const data = assignments;
    const columns = [{ name: 'Jabatan', selector: (item) => item.jabatan_desa?.nama || '-', sortable: true }, { name: 'Wilayah', selector: (item) => item.dusun?.nama || item.desa?.nama_desa || '-' }, { name: 'Pejabat Saat Ini', selector: (item) => item.perangkat_desa?.nama_lengkap || item.perangkat_desa?.user?.name || '-', sortable: true }, { name: 'Tanggal Mulai', selector: (item) => formatTanggal(item.tanggal_mulai), sortable: true }, { name: 'Nomor SK', selector: (item) => item.nomor_sk_pengangkatan || '-' }, { name: 'Status', cell: (item) => <Status value={item.status} /> }, { name: 'Aksi', cell: actions, right: true, minWidth: '250px' }];
    return <><Head title="Penugasan Jabatan" /><MasterResourcePage title="Penugasan Jabatan" group="Perangkat Desa" createHref="/penugasan-perangkat/create" createLabel="Tambah Penugasan" canCreateOverride={canCreate} data={data} columns={columns} showActions={false} searchFields={['jabatan_desa', 'dusun', 'tanggal_mulai']} searchPlaceholder="Cari penugasan..." emptyMessage="Belum ada penugasan aktif." /></>;
}
