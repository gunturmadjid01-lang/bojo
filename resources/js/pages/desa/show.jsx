import { Head } from '@inertiajs/react';
import Link from '@/components/permission-link';
import { ArrowLeft, Building2, Pencil } from 'lucide-react';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

export default function DesaShow({ desa }) {
    const logoUrl = desa?.logo ? `/storage/${desa.logo}` : '/logo-desa-bojo.svg';

    return (
        <VillageDashboardLayout>
            <Head title={`Detail Desa ${desa.nama_desa}`} />
            <section className="mx-auto max-w-5xl space-y-5">
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <Link href="/desa" className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link>
                        <div>
                            <p className="text-sm font-medium text-cyan-600">Pengaturan</p>
                            <h1 className="text-2xl font-bold text-slate-800">Detail Desa</h1>
                        </div>
                    </div>
                    <Link href={`/desa/${desa.id}/edit`} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-700"><Pencil className="size-4" /> Edit</Link>
                </div>

                <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-5 bg-gradient-to-r from-cyan-600 to-cyan-500 p-6 text-white sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-4">
                            <img src={logoUrl} alt={desa.nama_desa} className="size-20 rounded-full border-2 border-white/60 bg-white object-cover" />
                            <div>
                                <h2 className="text-2xl font-bold">{desa.nama_desa}</h2>
                                <p className="text-sm text-cyan-100">{desa.kecamatan || '-'} / {desa.kabupaten || '-'} / {desa.provinsi || '-'}</p>
                            </div>
                        </div>
                        <div className="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1.5 text-sm font-medium text-cyan-50">
                            <Building2 className="size-4" /> {desa.aktif ? 'Aktif' : 'Nonaktif'}
                        </div>
                    </div>

                    <div className="grid gap-5 p-6 sm:grid-cols-2">
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Kode Desa</p><p className="mt-1 font-medium text-slate-800">{desa.kode_desa || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Nama Desa</p><p className="mt-1 font-medium text-slate-800">{desa.nama_desa}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Kecamatan</p><p className="mt-1 font-medium text-slate-800">{desa.kecamatan || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Kabupaten</p><p className="mt-1 font-medium text-slate-800">{desa.kabupaten || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Provinsi</p><p className="mt-1 font-medium text-slate-800">{desa.provinsi || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Kode Pos</p><p className="mt-1 font-medium text-slate-800">{desa.kode_pos || '-'}</p></div>
                        <div className="sm:col-span-2"><p className="text-xs uppercase tracking-wide text-slate-400">Alamat Kantor</p><p className="mt-1 font-medium text-slate-800">{desa.alamat_kantor || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Telepon</p><p className="mt-1 font-medium text-slate-800">{desa.telepon || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Email</p><p className="mt-1 font-medium text-slate-800">{desa.email || '-'}</p></div>
                    </div>
                </div>
            </section>
        </VillageDashboardLayout>
    );
}
