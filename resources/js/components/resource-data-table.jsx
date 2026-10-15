import { Link, usePage } from '@inertiajs/react';
import DataTable from 'react-data-table-component';
import { Info, Pencil, Search, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { canAccessHref } from '@/lib/permissions';

const tableStyles = {
    headCells: {
        style: {
            backgroundColor: '#f8fafc',
            color: '#64748b',
            fontSize: '1.5rem',
            fontWeight: 700,
            minHeight: '96px',
            textTransform: 'uppercase',
        },
    },
    cells: { style: { color: '#475569', fontSize: '1.75rem', minHeight: '112px' } },
    rows: { style: { '&:hover': { backgroundColor: '#ecfeff' } } },
    pagination: { style: { borderTop: '1px solid #e2e8f0', color: '#64748b', fontSize: '1.75rem', minHeight: '112px' } },
};

export function TableActionButtons({ item, detailHref, editHref, onDelete, label }) {
    const { props } = usePage();
    const user = props?.auth?.user;
    const canView = detailHref ? canAccessHref(user, detailHref(item), 'view') : false;
    const canEdit = editHref ? canAccessHref(user, editHref(item), 'edit') : false;
    const canDelete = onDelete ? canAccessHref(user, detailHref?.(item) || editHref?.(item) || '', 'delete') : false;

    return (
        <div className="flex flex-wrap justify-end gap-1.5 py-1">
            {detailHref && canView && <Link href={detailHref(item)} className="inline-flex items-center gap-1 rounded-md bg-cyan-500 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-cyan-600" aria-label={`Detail ${label(item)}`}><Info className="size-3.5" /> Detail</Link>}
            {editHref && canEdit && <Link href={editHref(item)} className="inline-flex items-center gap-1 rounded-md bg-emerald-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700" aria-label={`Edit ${label(item)}`}><Pencil className="size-3.5" /> Edit</Link>}
            {onDelete && canDelete && <button type="button" onClick={() => onDelete(item)} className="inline-flex items-center gap-1 rounded-md bg-red-500 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-red-600" aria-label={`Hapus ${label(item)} `}><Trash2 className="size-3.5" /> Hapus</button>}
        </div>
    );
}

export default function ResourceDataTable({ data = [], columns, searchFields = [], searchPlaceholder = 'Cari data...', emptyMessage = 'Data tidak ditemukan.', detailHref, editHref, onDelete, itemLabel = (item) => item.nama ?? item.name ?? item.id, showActions = true, filters = true }) {
    const [search, setSearch] = useState('');
    const [filterOpen, setFilterOpen] = useState(false);
    const [filter, setFilter] = useState({ dusun: '', rw: '', rt: '', status: '', dateFrom: '', dateTo: '' });
    const values = (field) => [...new Set(data.map((item) => item?.[field]?.nama || item?.[field]?.nomor || item?.[field]).filter(Boolean))].sort();
    const filterOptions = { dusun: values('dusun'), rw: values('rw'), rt: values('rt'), status: [...new Set(data.map((item) => item?.status || item?.status_penduduk || item?.status_verifikasi).filter(Boolean))].sort() };

    const filteredData = useMemo(() => {
        const term = search.trim().toLowerCase();
        return data.filter((item) => {
            const matchesSearch = !term || searchFields.some((field) => String(typeof field === 'function' ? field(item) : item[field] ?? '').toLowerCase().includes(term));
            const date = Object.entries(item).find(([key, value]) => /^tanggal_/.test(key) && value)?.[1] || item.created_at || '';
            const status = item?.status || item?.status_penduduk || item?.status_verifikasi || '';
            return matchesSearch && (!filter.dusun || String(item?.dusun?.nama || item?.dusun || '') === filter.dusun) && (!filter.rw || String(item?.rw?.nomor || item?.rw || '') === filter.rw) && (!filter.rt || String(item?.rt?.nomor || item?.rt || '') === filter.rt) && (!filter.status || status === filter.status) && (!filter.dateFrom || date.slice(0, 10) >= filter.dateFrom) && (!filter.dateTo || date.slice(0, 10) <= filter.dateTo);
        });
    }, [data, search, searchFields, filter]);

    const actionColumn = {
        name: 'Aksi',
        cell: (item) => <TableActionButtons item={item} detailHref={detailHref} editHref={editHref} onDelete={onDelete} label={itemLabel} />,
        right: true,
        minWidth: '250px',
        ignoreRowClick: true,
    };

    return (
        <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div className="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
                <p className="text-sm text-slate-600"><span className="font-semibold text-cyan-600">{filteredData.length}</span> data ditemukan</p>
                <label className="relative block">
                    <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                    <input value={search} onChange={(event) => setSearch(event.target.value)} placeholder={searchPlaceholder} className="h-10 w-full rounded-md border border-slate-200 pl-9 pr-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100 sm:w-64" />
                </label>
                {filters && <button type="button" onClick={() => setFilterOpen((value) => !value)} className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm font-medium text-slate-600">Filter</button>}
            </div>
            {filters && filterOpen && <div className="grid gap-3 border-b border-slate-100 bg-slate-50 p-4 sm:grid-cols-2 lg:grid-cols-6"><FilterSelect label="Dusun" value={filter.dusun} options={filterOptions.dusun} onChange={(value) => setFilter((current) => ({ ...current, dusun: value }))} /><FilterSelect label="RW" value={filter.rw} options={filterOptions.rw} onChange={(value) => setFilter((current) => ({ ...current, rw: value }))} /><FilterSelect label="RT" value={filter.rt} options={filterOptions.rt} onChange={(value) => setFilter((current) => ({ ...current, rt: value }))} /><FilterSelect label="Status" value={filter.status} options={filterOptions.status} onChange={(value) => setFilter((current) => ({ ...current, status: value }))} /><DateFilter label="Dari tanggal" value={filter.dateFrom} onChange={(value) => setFilter((current) => ({ ...current, dateFrom: value }))} /><DateFilter label="Sampai tanggal" value={filter.dateTo} onChange={(value) => setFilter((current) => ({ ...current, dateTo: value }))} /></div>}
            <DataTable columns={showActions ? [...columns, actionColumn] : columns} data={filteredData} keyField="id" customStyles={tableStyles} pagination paginationPerPage={10} paginationRowsPerPageOptions={[10, 25, 50, 100]} noDataComponent={<p className="py-10 text-sm text-slate-500">{emptyMessage}</p>} />
        </div>
    );
}

function FilterSelect({ label, value, options, onChange }) { return <label className="text-xs font-medium text-slate-600">{label}<select value={value} onChange={(event) => onChange(event.target.value)} className="mt-1 h-9 w-full rounded-md border border-slate-200 bg-white px-2 text-sm"><option value="">Semua</option>{options.map((option) => <option key={`${label}-${option}`} value={option}>{option}</option>)}</select></label>; }
function DateFilter({ label, value, onChange }) { return <label className="text-xs font-medium text-slate-600">{label}<input type="date" value={value} onChange={(event) => onChange(event.target.value)} className="mt-1 h-9 w-full rounded-md border border-slate-200 bg-white px-2 text-sm" /></label>; }
