import { Head, useForm, usePage } from '@inertiajs/react';
import { Check, ShieldCheck } from 'lucide-react';
import { useMemo, useState } from 'react';
import FormSelect from '@/components/form-select';
import ResourceDataTable from '@/components/resource-data-table';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';
import { canAccessHref } from '@/lib/permissions';

function PermissionToggle({ permission, checked, onToggle, disabled = false }) {
    return <button type="button" disabled={disabled} onClick={() => onToggle(permission)} className={`inline-flex h-8 w-8 items-center justify-center rounded-md border disabled:cursor-not-allowed disabled:opacity-50 ${checked ? 'border-emerald-200 bg-emerald-100 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-400'}`} aria-label={`Toggle ${permission}`}>{checked && <Check className="size-4" />}</button>;
}

function AllPermissionToggle({ permissions, checked, onToggle, disabled = false }) {
    return (
        <button
            type="button"
            disabled={disabled}
            onClick={() => onToggle(permissions)}
            className={`inline-flex h-8 w-8 items-center justify-center rounded-md border ${checked ? 'border-emerald-200 bg-emerald-100 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-400'}`}
            aria-label={`Toggle all permissions for ${permissions.join(', ')}`}
            title="Checklist semua aksi"
        >
            {checked && <Check className="size-4" />}
        </button>
    );
}

export default function PermissionMatrixPage({ permissionGroups: permissionMatrix = [], roles = [], rolePermissions = {}, selectedRoleId: initialSelectedRoleId = null }) {
    const canEdit = canAccessHref(usePage().props?.auth?.user, '/permissions', 'edit');
    const groupNames = useMemo(() => [...new Set(permissionMatrix.map((row) => row.group))], [permissionMatrix]);
    const [selectedGroup, setSelectedGroup] = useState(groupNames[0] || '');
    const [selectedRoleId, setSelectedRoleId] = useState(initialSelectedRoleId || roles[0]?.id || '');
    const selectedPermissions = rolePermissions[selectedRoleId] || [];
    const { data, setData, processing, post } = useForm({ role_id: String(selectedRoleId), permissions: Object.fromEntries(permissionMatrix.flatMap((row) => row.permissions.map((permission) => [permission, selectedPermissions.includes(permission)]))) });
    const visibleRows = useMemo(() => permissionMatrix.filter((row) => !selectedGroup || row.group === selectedGroup).map((row) => ({ ...row, id: row.title })), [permissionMatrix, selectedGroup]);
    const togglePermission = (permission) => setData('permissions', { ...data.permissions, [permission]: !(data.permissions[permission] ?? false) });
    const toggleAllPermissions = (permissions) => {
        const nextValue = !permissions.every((permission) => data.permissions[permission] ?? false);
        setData('permissions', { ...data.permissions, ...Object.fromEntries(permissions.map((permission) => [permission, nextValue])) });
    };
    const changeRole = (event) => {
        const roleId = event.target.value;
        const permissions = rolePermissions[roleId] || [];
        setSelectedRoleId(roleId);
        setData({ role_id: String(roleId), permissions: Object.fromEntries(permissionMatrix.flatMap((row) => row.permissions.map((permission) => [permission, permissions.includes(permission)]))) });
    };
    const columns = [
        {
            name: 'All',
            center: true,
            cell: (row) => {
                const isChecked = row.permissions.every((permission) => data.permissions[permission] ?? false);
                return <AllPermissionToggle disabled={!canEdit} permissions={row.permissions} checked={isChecked} onToggle={toggleAllPermissions} />;
            },
        },
        ...['View', 'Create', 'Edit', 'Delete'].map((name, index) => ({ name, center: true, cell: (row) => <PermissionToggle disabled={!canEdit} permission={row.permissions[index]} checked={data.permissions[row.permissions[index]]} onToggle={togglePermission} /> })),
    ];

    return <VillageDashboardLayout><Head title="Matrix Permission" /><section className="mx-auto max-w-8xl space-y-5"><div><p className="text-sm font-medium text-cyan-600">Manajemen Akses</p><h1 className="mt-1 text-2xl font-bold text-slate-800">Matriks Permission</h1><p className="mt-1 text-sm text-slate-500">Pilih kelompok menu di sisi kiri untuk mengatur permission berdasarkan menu yang aktif.</p></div><form onSubmit={(event) => { event.preventDefault(); if (canEdit) post('/permissions', { preserveScroll: true }); }} className="space-y-5"><div className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><label className="block w-full max-w-sm"><span className="mb-2 block text-sm font-medium text-slate-700">Pilih Role</span><FormSelect value={data.role_id} options={roles.map((role) => ({ value: String(role.id), label: role.name }))} onChange={(value) => changeRole({ target: { value } })} placeholder="Pilih role" /></label></div><div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"><div className="flex flex-wrap gap-2">{groupNames.map((group) => (<button key={group} type="button" onClick={() => setSelectedGroup(group)} className={`rounded-full border px-3 py-1.5 text-sm font-medium transition ${selectedGroup === group ? 'border-cyan-600 bg-cyan-50 text-cyan-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'}`}>{group}</button>))}</div></div><ResourceDataTable data={visibleRows} showActions={false} searchFields={['group', 'title']} searchPlaceholder="Cari permission..." columns={[{ name: 'Kelompok Menu', selector: (row) => row.group, sortable: true }, { name: 'Judul Permission', selector: (row) => row.title, sortable: true }, ...columns]} />{canEdit && <div className="flex justify-end"><button type="submit" disabled={processing} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-700 disabled:opacity-60"><ShieldCheck className="size-4" />{processing ? 'Menyimpan...' : 'Simpan Permission'}</button></div>}</form></section></VillageDashboardLayout>;
}
