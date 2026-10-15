import { Head } from '@inertiajs/react';
import Link from '@/components/permission-link';
import { ArrowLeft, MapPinned, Pencil } from 'lucide-react';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

export default function DusunShow({ dusun }) {
    return (
        <VillageDashboardLayout>
            <Head title={`Detail ${dusun.nama}`} />
            <section className="mx-auto max-w-8xl space-y-5">
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <Link href="/dusun" className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link>
                        <div>
                            <p className="text-sm font-medium text-cyan-600">Master Wilayah</p>
                            <h1 className="text-2xl font-bold text-slate-800">Detail Dusun</h1>
                        </div>
                    </div>
                    <Link href={`/dusun/${dusun.id}/edit`} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-700"><Pencil className="size-4" /> Edit</Link>
                </div>

                <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center gap-4 bg-gradient-to-r from-cyan-600 to-cyan-500 p-6 text-white">
                        <div className="flex size-16 items-center justify-center rounded-full bg-white/20"><MapPinned className="size-8" /></div>
                        <div>
                            <h2 className="text-xl font-bold">{dusun.nama}</h2>
                            <p className="text-sm text-cyan-100">{dusun.kode || 'Tanpa kode'}</p>
                        </div>
                    </div>

                    <div className="grid gap-5 p-6 sm:grid-cols-2">
                        <div>
                            <p className="text-xs uppercase tracking-wide text-slate-400">Nama dusun</p>
                            <p className="mt-1 font-medium text-slate-800">{dusun.nama}</p>
                        </div>
                        <div>
                            <p className="text-xs uppercase tracking-wide text-slate-400">Kode</p>
                            <p className="mt-1 font-medium text-slate-800">{dusun.kode || '-'}</p>
                        </div>
                        <div>
                            <p className="text-xs uppercase tracking-wide text-slate-400">Desa</p>
                            <p className="mt-1 font-medium text-slate-800">{dusun.desa?.nama_desa || '-'}</p>
                        </div>
                        <div>
                            <p className="text-xs uppercase tracking-wide text-slate-400">Status</p>
                            <p className="mt-1 font-medium text-slate-800">{dusun.aktif ? 'Aktif' : 'Nonaktif'}</p>
                        </div>
                    </div>
                </div>
                <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 className="text-lg font-bold text-slate-800">Struktur RW dan RT</h2>
                    <p className="mt-1 text-sm text-slate-500">Seluruh RW dan RT dikelola melalui form Dusun.</p>
                    <div className="mt-4 grid gap-4 md:grid-cols-2">
                        {(dusun.rws || []).map((rw) => <div key={rw.id} className="rounded-md border border-slate-200 p-4"><div className="flex items-center justify-between"><p className="font-semibold text-slate-800">RW {rw.nomor}</p><span className={`rounded-full px-2 py-1 text-xs ${rw.aktif ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>{rw.aktif ? 'Aktif' : 'Nonaktif'}</span></div><div className="mt-3 flex flex-wrap gap-2">{(rw.rts || []).map((rt) => <span key={rt.id} className={`rounded-md border px-2.5 py-1 text-sm ${rt.aktif ? 'border-cyan-200 bg-cyan-50 text-cyan-800' : 'border-slate-200 bg-slate-50 text-slate-500'}`}>RT {rt.nomor}</span>)}{!rw.rts?.length && <span className="text-sm text-slate-400">Belum ada RT</span>}</div></div>)}
                        {!dusun.rws?.length && <p className="text-sm text-slate-500">Belum ada RW dan RT pada dusun ini.</p>}
                    </div>
                </div>
            </section>
        </VillageDashboardLayout>
    );
}
