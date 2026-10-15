import { Head } from '@inertiajs/react';
import Link from '@/components/permission-link';
import { ArrowLeft, Pencil, BriefcaseBusiness } from 'lucide-react';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

export default function SosialEkonomiPendudukShow({ sosialEkonomiPenduduk }) {
    return (
        <VillageDashboardLayout>
            <Head title={`Detail Sosial Ekonomi Penduduk`} />
            <section className="mx-auto max-w-8xl space-y-5">
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <Link href="/sosial-ekonomi-penduduk" className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link>
                        <div>
                            <p className="text-sm font-medium text-cyan-600">Data Sosial</p>
                            <h1 className="text-2xl font-bold text-slate-800">Detail Sosial Ekonomi Penduduk</h1>
                        </div>
                    </div>
                    <Link href={`/sosial-ekonomi-penduduk/${sosialEkonomiPenduduk.id}/edit`} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-700"><Pencil className="size-4" /> Edit</Link>
                </div>

                <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center gap-4 bg-gradient-to-r from-cyan-600 to-cyan-500 p-6 text-white">
                        <div className="flex size-16 items-center justify-center rounded-full bg-white/20"><BriefcaseBusiness className="size-8" /></div>
                        <div>
                            <h2 className="text-xl font-bold">{sosialEkonomiPenduduk.penduduk?.nama_lengkap || 'Penduduk'}</h2>
                            <p className="text-sm text-cyan-100">{sosialEkonomiPenduduk.penduduk?.nik || 'NIK belum ada'}</p>
                        </div>
                    </div>

                    <div className="grid gap-5 p-6 sm:grid-cols-2">
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Nama Penduduk</p><p className="mt-1 font-medium text-slate-800">{sosialEkonomiPenduduk.penduduk?.nama_lengkap || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">NIK</p><p className="mt-1 font-medium text-slate-800">{sosialEkonomiPenduduk.penduduk?.nik || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Status Pekerjaan</p><p className="mt-1 font-medium text-slate-800">{sosialEkonomiPenduduk.status_pekerjaan || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Penghasilan Bulanan</p><p className="mt-1 font-medium text-slate-800">{sosialEkonomiPenduduk.penghasilan_bulanan ? `Rp ${Number(sosialEkonomiPenduduk.penghasilan_bulanan).toLocaleString('id-ID')}` : '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Kategori Penghasilan</p><p className="mt-1 font-medium text-slate-800">{sosialEkonomiPenduduk.kategori_penghasilan || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Jaminan Kesehatan</p><p className="mt-1 font-medium text-slate-800">{sosialEkonomiPenduduk.jaminan_kesehatan || '-'}</p></div>
                    </div>
                </div>
            </section>
        </VillageDashboardLayout>
    );
}
