import { router } from '@inertiajs/react';
import MasterResourcePage from '@/components/master-resource-page';

export default function UserIndex({ users = [] }) {
    return <MasterResourcePage title="Data User" group="Manajemen Akses" createHref="/users/create" createLabel="Tambah User" data={users} searchFields={['name', 'username', (item) => item.roles?.map((role) => role.name).join(' ')]} searchPlaceholder="Cari user..." emptyMessage="User tidak ditemukan." itemLabel={(item) => item.name} detailHref={(item) => `/users/${item.id}`} editHref={(item) => `/users/${item.id}/edit`} onDelete={(item) => window.confirm(`Hapus user ${item.name}?`) && router.delete(`/users/${item.id}`)} columns={[{ name: 'Nama', selector: (item) => item.name, sortable: true }, { name: 'Username', selector: (item) => item.username, sortable: true }, { name: 'Jenis Kelamin', selector: (item) => item.jenis_kelamin || '-' }, { name: 'Role', cell: (item) => <div className="flex flex-wrap gap-1">{item.roles?.map((role) => <span key={role.id} className="rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-medium text-cyan-700">{role.name}</span>)}</div> }]} />;
}
