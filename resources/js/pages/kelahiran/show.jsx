import { Head } from '@inertiajs/react';
import Link from '@/components/permission-link';
import { ArrowLeft, Baby, Pencil } from 'lucide-react';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

export default function KelahiranShow({ kelahiran }) {
    return (
        <VillageDashboardLayout>
            <Head title={`Detail Kelahiran ${kelahiran.penduduk?.nama_lengkap || ''}`} />
            <section className="mx-auto max-w-8xl space-y-5">
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <Link href="/kelahiran" className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link>
                        <div>
                            <p className="text-sm font-medium text-cyan-600">Peristiwa Kependudukan</p>
                            <h1 className="text-2xl font-bold text-slate-800">Detail Kelahiran</h1>
                        </div>
                    </div>
                    <Link href={`/kelahiran/${kelahiran.id}/edit`} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-700"><Pencil className="size-4" /> Edit</Link>
                </div>

                <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center gap-4 bg-gradient-to-r from-cyan-600 to-cyan-500 p-6 text-white">
                        <div className="flex size-16 items-center justify-center rounded-full bg-white/20"><Baby className="size-8" /></div>
                        <div>
                            <h2 className="text-xl font-bold">{kelahiran.penduduk?.nama_lengkap || '-'}</h2>
                            <p className="text-sm text-cyan-100">{kelahiran.nomor_akta_kelahiran || 'Belum ada nomor akta'}</p>
                        </div>
                    </div>

                    <div className="grid gap-5 p-6 md:grid-cols-2">
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Penduduk</p><p className="mt-1 font-medium text-slate-800">{kelahiran.penduduk?.nama_lengkap || '-'} ({kelahiran.penduduk?.nik || '-'})</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Kartu Keluarga</p><p className="mt-1 font-medium text-slate-800">{kelahiran.kartu_keluarga?.nomor_kk || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Ayah</p><p className="mt-1 font-medium text-slate-800">{kelahiran.ayah?.nama_lengkap || '-'} ({kelahiran.ayah?.nik || '-'})</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Ibu</p><p className="mt-1 font-medium text-slate-800">{kelahiran.ibu?.nama_lengkap || '-'} ({kelahiran.ibu?.nik || '-'})</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Tempat / Tanggal Lahir</p><p className="mt-1 font-medium text-slate-800">{[kelahiran.tempat_lahir, kelahiran.tanggal_lahir].filter(Boolean).join(', ') || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Anak Ke</p><p className="mt-1 font-medium text-slate-800">{kelahiran.anak_ke || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Berat / Panjang</p><p className="mt-1 font-medium text-slate-800">{[kelahiran.berat_lahir && `${kelahiran.berat_lahir} kg`, kelahiran.panjang_lahir && `${kelahiran.panjang_lahir} cm`].filter(Boolean).join(' / ') || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Tanggal Dilaporkan</p><p className="mt-1 font-medium text-slate-800">{kelahiran.tanggal_dilaporkan || '-'}</p></div>
                        <div className="md:col-span-2"><p className="text-xs uppercase tracking-wide text-slate-400">Catatan</p><p className="mt-1 font-medium text-slate-800">{kelahiran.catatan || '-'}</p></div>
                    </div>
                </div>
            </section>
        </VillageDashboardLayout>
    );
}
