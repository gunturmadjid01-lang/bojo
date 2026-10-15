import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Pencil, ShieldCheck } from 'lucide-react';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';
import { canAccessHref } from '@/lib/permissions';

export default function JabatanDesaShow({ jabatanDesa }) {
    const user = usePage().props?.auth?.user;
    const canEdit = !jabatanDesa.bawaan_sistem && canAccessHref(user, `/jabatan-desa/${jabatanDesa.id}/edit`, 'edit');

    return (
        <VillageDashboardLayout>
            <Head title="Detail Jabatan Desa" />
            <section className="mx-auto max-w-4xl space-y-5">
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <Link href="/jabatan-desa" className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link>
                        <div><p className="text-sm font-medium text-cyan-600">Perangkat Desa</p><h1 className="text-2xl font-bold text-slate-800">Detail Jabatan Desa</h1></div>
                    </div>
                    {canEdit && <Link href={`/jabatan-desa/${jabatanDesa.id}/edit`} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-700"><Pencil className="size-4" /> Edit</Link>}
                </div>
                <div className="rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center gap-3 border-b border-slate-100 p-5"><ShieldCheck className="size-5 text-cyan-600" /><h2 className="font-semibold text-slate-800">Informasi Jabatan</h2></div>
                    <div className="grid gap-5 p-6 sm:grid-cols-2">
                        {[['Kode', jabatanDesa.kode], ['Nama', jabatanDesa.nama], ['Tingkat', jabatanDesa.tingkat], ['Status', jabatanDesa.aktif ? 'Aktif' : 'Nonaktif'], ['Jenis', jabatanDesa.bawaan_sistem ? 'Bawaan sistem' : 'Custom']].map(([label, value]) => <div key={label}><p className="text-xs uppercase tracking-wide text-slate-400">{label}</p><p className="mt-1 font-medium text-slate-800">{value || '-'}</p></div>)}
                    </div>
                </div>
            </section>
        </VillageDashboardLayout>
    );
}
