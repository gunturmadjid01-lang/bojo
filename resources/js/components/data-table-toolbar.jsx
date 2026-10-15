import { Search } from 'lucide-react';

export default function DataTableToolbar({ pageSize, onPageSizeChange, search, onSearchChange }) {
    return (
        <div className="flex flex-col gap-3 border-b border-slate-100 px-2 py-3 sm:flex-row sm:items-center sm:justify-between">
            <label className="flex items-center gap-1 text-sm text-slate-700">
                <select value={pageSize} onChange={(event) => onPageSizeChange(Number(event.target.value))} className="h-8 rounded border border-slate-300 bg-white px-2 text-sm outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500" aria-label="Jumlah data per halaman">
                    {[5, 10, 25, 50].map((size) => <option key={size} value={size}>{size}</option>)}
                </select>
                <span>data</span>
            </label>
            <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                <span>Cari:</span>
                <span className="relative">
                    <Search className="pointer-events-none absolute left-2 top-1/2 size-3.5 -translate-y-1/2 text-slate-400" aria-hidden="true" />
                    <input value={search} onChange={(event) => onSearchChange(event.target.value)} className="h-8 w-full rounded border border-slate-400 bg-white pl-7 pr-2 font-normal outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 sm:w-48" aria-label="Cari data penduduk" />
                </span>
            </label>
        </div>
    );
}
