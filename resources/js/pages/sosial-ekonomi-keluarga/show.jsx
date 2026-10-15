import { Head } from '@inertiajs/react';
import Link from '@/components/permission-link';
import { ArrowLeft, Pencil, UsersRound } from 'lucide-react';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

export default function SosialEkonomiKeluargaShow({ sosialEkonomiKeluarga }) {
    return (
        <VillageDashboardLayout>
            <Head title={`Detail Sosial Ekonomi Keluarga`} />
            <section className="mx-auto max-w-8xl space-y-5">
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <Link href="/sosial-ekonomi-keluarga" className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link>
                        <div>
                            <p className="text-sm font-medium text-cyan-600">Data Sosial</p>
                            <h1 className="text-2xl font-bold text-slate-800">Detail Sosial Ekonomi Keluarga</h1>
                        </div>
                    </div>
                    <Link href={`/sosial-ekonomi-keluarga/${sosialEkonomiKeluarga.id}/edit`} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-700"><Pencil className="size-4" /> Edit</Link>
                </div>

                <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center gap-4 bg-gradient-to-r from-cyan-600 to-cyan-500 p-6 text-white">
                        <div className="flex size-16 items-center justify-center rounded-full bg-white/20"><UsersRound className="size-8" /></div>
                        <div>
                            <h2 className="text-xl font-bold">{sosialEkonomiKeluarga.kartu_keluarga?.nomor_kk || 'Keluarga'}</h2>
                            <p className="text-sm text-cyan-100">{sosialEkonomiKeluarga.kategori_ekonomi || 'Kategori ekonomi belum diisi'}</p>
                        </div>
                    </div>

                    <div className="grid gap-5 p-6 sm:grid-cols-2">
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Nomor KK</p><p className="mt-1 font-medium text-slate-800">{sosialEkonomiKeluarga.kartu_keluarga?.nomor_kk || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Status Kepemilikan Rumah</p><p className="mt-1 font-medium text-slate-800">{sosialEkonomiKeluarga.status_kepemilikan_rumah || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Kondisi Rumah</p><p className="mt-1 font-medium text-slate-800">{sosialEkonomiKeluarga.kondisi_rumah || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Kategori Ekonomi</p><p className="mt-1 font-medium text-slate-800">{sosialEkonomiKeluarga.kategori_ekonomi || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Sumber Air</p><p className="mt-1 font-medium text-slate-800">{sosialEkonomiKeluarga.sumber_air || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Tanggal Pendataan</p><p className="mt-1 font-medium text-slate-800">{sosialEkonomiKeluarga.tanggal_pendataan || '-'}</p></div>
                    </div>
                </div>
            </section>
        </VillageDashboardLayout>
    );
}
