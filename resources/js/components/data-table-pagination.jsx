import { ChevronLeft, ChevronRight } from 'lucide-react';

export default function DataTablePagination({ page, pageCount, total, from, to, onPageChange }) {
    const pages = Array.from({ length: pageCount }, (_, index) => index + 1);

    return (
        <div className="flex flex-col gap-3 px-2 py-3 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between">
            <span>{total === 0 ? '0 data' : `${from} sampai ${to} dari ${total} data`}</span>
            <div className="flex items-center gap-1">
                <button type="button" disabled={page === 1} onClick={() => onPageChange(page - 1)} className="rounded px-2 py-1.5 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40" aria-label="Halaman sebelumnya">
                    <ChevronLeft className="size-4" />
                </button>
                {pages.map((pageNumber) => (
                    <button key={pageNumber} type="button" onClick={() => onPageChange(pageNumber)} className={`min-w-8 rounded px-2 py-1.5 ${pageNumber === page ? 'border border-slate-300 bg-slate-100 text-slate-900' : 'hover:bg-slate-100'}`}>
                        {pageNumber}
                    </button>
                ))}
                <button type="button" disabled={page === pageCount || pageCount === 0} onClick={() => onPageChange(page + 1)} className="rounded px-2 py-1.5 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40" aria-label="Halaman berikutnya">
                    <ChevronRight className="size-4" />
                </button>
            </div>
        </div>
    );
}
