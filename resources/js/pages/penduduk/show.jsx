import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, UserRound, Pencil } from 'lucide-react';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';
import { canAccessHref } from '@/lib/permissions';

export default function PendudukShow({ penduduk }) {
    const { props } = usePage();
    const user = props?.auth?.user;
    const canEdit = canAccessHref(user, `/penduduk/${penduduk.id}/edit`, 'edit');
    return (
        <VillageDashboardLayout>
            <Head title={`Detail ${penduduk.nama_lengkap}`} />
            <section className="mx-auto max-w-8xl space-y-5">
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <Link href="/penduduk" className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link>
                        <div>
                            <p className="text-sm font-medium text-cyan-600">Kependudukan</p>
                            <h1 className="text-2xl font-bold text-slate-800">Detail Penduduk</h1>
                        </div>
                    </div>
                    {canEdit && <Link href={`/penduduk/${penduduk.id}/edit`} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-700"><Pencil className="size-4" /> Edit</Link>}
                </div>

                <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center gap-4 bg-gradient-to-r from-cyan-600 to-cyan-500 p-6 text-white">
                        <div className="flex size-16 items-center justify-center rounded-full bg-white/20"><UserRound className="size-8" /></div>
                        <div>
                            <h2 className="text-xl font-bold">{penduduk.nama_lengkap}</h2>
                            <p className="text-sm text-cyan-100">{penduduk.nik}</p>
                        </div>
                    </div>

                    <div className="grid gap-5 p-6 md:grid-cols-2">
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">NIK</p><p className="mt-1 font-medium text-slate-800">{penduduk.nik || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Nama</p><p className="mt-1 font-medium text-slate-800">{penduduk.nama_lengkap || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Jenis Kelamin</p><p className="mt-1 font-medium text-slate-800">{penduduk.jenis_kelamin || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Tempat, Tanggal Lahir</p><p className="mt-1 font-medium text-slate-800">{[penduduk.tempat_lahir, penduduk.tanggal_lahir].filter(Boolean).join(', ') || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Agama</p><p className="mt-1 font-medium text-slate-800">{penduduk.agama || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Pendidikan</p><p className="mt-1 font-medium text-slate-800">{penduduk.pendidikan?.nama || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Pekerjaan</p><p className="mt-1 font-medium text-slate-800">{penduduk.pekerjaan?.nama || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Dusun</p><p className="mt-1 font-medium text-slate-800">{penduduk.dusun?.nama || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">RW</p><p className="mt-1 font-medium text-slate-800">{penduduk.rw?.nomor ? `RW ${penduduk.rw.nomor}` : '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">RT</p><p className="mt-1 font-medium text-slate-800">{penduduk.rt?.nomor ? `RT ${penduduk.rt.nomor}` : '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Status</p><p className="mt-1 font-medium text-slate-800">{penduduk.status_penduduk || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Masih Hidup</p><p className="mt-1 font-medium text-slate-800">{penduduk.masih_hidup ? 'Ya' : 'Tidak'}</p></div>
                        <div className="md:col-span-2"><p className="text-xs uppercase tracking-wide text-slate-400">Alamat</p><p className="mt-1 font-medium text-slate-800">{penduduk.alamat || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Telepon</p><p className="mt-1 font-medium text-slate-800">{penduduk.telepon || '-'}</p></div>
                    </div>
                </div>
            </section>
        </VillageDashboardLayout>
    );
}
