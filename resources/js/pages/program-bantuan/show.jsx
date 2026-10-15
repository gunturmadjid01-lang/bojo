import { Head } from '@inertiajs/react';
import Link from '@/components/permission-link';
import { ArrowLeft, HandCoins, Pencil } from 'lucide-react';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

export default function ProgramBantuanShow({ programBantuan }) {
    return (
        <VillageDashboardLayout>
            <Head title={`Detail ${programBantuan.nama}`} />
            <section className="mx-auto max-w-8xl space-y-5">
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <Link href="/program-bantuan" className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link>
                        <div>
                            <p className="text-sm font-medium text-cyan-600">Master Data</p>
                            <h1 className="text-2xl font-bold text-slate-800">Detail Program Bantuan</h1>
                        </div>
                    </div>
                    <Link href={`/program-bantuan/${programBantuan.id}/edit`} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-700"><Pencil className="size-4" /> Edit</Link>
                </div>

                <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center gap-4 bg-gradient-to-r from-cyan-600 to-cyan-500 p-6 text-white">
                        <div className="flex size-16 items-center justify-center rounded-full bg-white/20"><HandCoins className="size-8" /></div>
                        <div>
                            <h2 className="text-xl font-bold">{programBantuan.nama}</h2>
                            <p className="text-sm text-cyan-100">{programBantuan.kode || 'Kode belum diatur'}</p>
                        </div>
                    </div>

                    <div className="grid gap-5 p-6 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <p className="text-xs uppercase tracking-wide text-slate-400">Kode</p>
                            <p className="mt-1 font-medium text-slate-800">{programBantuan.kode || '-'}</p>
                        </div>
                        <div>
                            <p className="text-xs uppercase tracking-wide text-slate-400">Sumber</p>
                            <p className="mt-1 font-medium text-slate-800">{programBantuan.sumber || '-'}</p>
                        </div>
                        <div>
                            <p className="text-xs uppercase tracking-wide text-slate-400">Tahun</p>
                            <p className="mt-1 font-medium text-slate-800">{programBantuan.tahun || '-'}</p>
                        </div>
                        <div>
                            <p className="text-xs uppercase tracking-wide text-slate-400">Tanggal Mulai</p>
                            <p className="mt-1 font-medium text-slate-800">{programBantuan.tanggal_mulai || '-'}</p>
                        </div>
                        <div>
                            <p className="text-xs uppercase tracking-wide text-slate-400">Tanggal Selesai</p>
                            <p className="mt-1 font-medium text-slate-800">{programBantuan.tanggal_selesai || '-'}</p>
                        </div>
                        <div>
                            <p className="text-xs uppercase tracking-wide text-slate-400">Status</p>
                            <p className="mt-1 font-medium text-slate-800">{programBantuan.status || '-'}</p>
                        </div>
                        <div className="sm:col-span-2 lg:col-span-3">
                            <p className="text-xs uppercase tracking-wide text-slate-400">Keterangan</p>
                            <p className="mt-1 font-medium text-slate-800">{programBantuan.keterangan || '-'}</p>
                        </div>
                    </div>
                </div>
            </section>
        </VillageDashboardLayout>
    );
}
