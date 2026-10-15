import { Head, Link, usePage } from '@inertiajs/react';
import { Building2, Pencil, Upload } from 'lucide-react';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';
import { canAccessHref } from '@/lib/permissions';

export default function DesaIndex({ desa }) {
    const user = usePage().props?.auth?.user;
    const canCreate = canAccessHref(user, '/desa/create', 'create');
    const canEdit = desa ? canAccessHref(user, `/desa/${desa.id}/edit`, 'edit') : false;
    const logoUrl = desa?.logo ? `/storage/${desa.logo}` : '/logo-desa-bojo.svg';

    return (
        <VillageDashboardLayout>
            <Head title="Setting Desa" />
            <section className="mx-auto max-w-8xl space-y-5">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-medium text-cyan-600">Pengaturan</p>
                        <h1 className="mt-1 text-2xl font-bold text-slate-800">Setting Desa</h1>
                    </div>
                    {!desa && canCreate && <Link href="/desa/create" className="inline-flex items-center justify-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-700">
                        <Upload className="size-4" /> Kelola Desa
                    </Link>}
                </div>

                {!desa ? (
                    <div className="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500 shadow-sm">
                        Belum ada data desa. Silakan tambah data desa terlebih dahulu.
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                        <div className="flex flex-col gap-6 p-6 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex items-center gap-4">
                                <img src={logoUrl} alt={desa.nama_desa} className="size-20 rounded-full border border-slate-200 object-cover bg-slate-50" />
                                <div>
                                    <p className="text-xs uppercase tracking-wide text-slate-400">Desa</p>
                                    <h2 className="text-2xl font-bold text-slate-800">{desa.nama_desa}</h2>
                                    <p className="text-sm text-slate-600">{desa.kecamatan || '-'} / {desa.kabupaten || '-'} / {desa.provinsi || '-'}</p>
                                </div>
                            </div>
                            {canEdit && <Link href={`/desa/${desa.id}/edit`} className="inline-flex items-center gap-2 rounded-md border border-cyan-200 bg-cyan-50 px-4 py-2.5 text-sm font-semibold text-cyan-700 hover:bg-cyan-100">
                                <Pencil className="size-4" /> Edit Desa
                            </Link>}
                        </div>

                        <div className="grid gap-5 border-t border-slate-100 p-6 sm:grid-cols-2">
                            <div><p className="text-xs uppercase tracking-wide text-slate-400">Kode Desa</p><p className="mt-1 font-medium text-slate-800">{desa.kode_desa || '-'}</p></div>
                            <div><p className="text-xs uppercase tracking-wide text-slate-400">Nama Desa</p><p className="mt-1 font-medium text-slate-800">{desa.nama_desa}</p></div>
                            <div><p className="text-xs uppercase tracking-wide text-slate-400">Kecamatan</p><p className="mt-1 font-medium text-slate-800">{desa.kecamatan || '-'}</p></div>
                            <div><p className="text-xs uppercase tracking-wide text-slate-400">Kabupaten</p><p className="mt-1 font-medium text-slate-800">{desa.kabupaten || '-'}</p></div>
                            <div><p className="text-xs uppercase tracking-wide text-slate-400">Provinsi</p><p className="mt-1 font-medium text-slate-800">{desa.provinsi || '-'}</p></div>
                            <div><p className="text-xs uppercase tracking-wide text-slate-400">Kode Pos</p><p className="mt-1 font-medium text-slate-800">{desa.kode_pos || '-'}</p></div>
                            <div className="sm:col-span-2"><p className="text-xs uppercase tracking-wide text-slate-400">Alamat Kantor</p><p className="mt-1 font-medium text-slate-800">{desa.alamat_kantor || '-'}</p></div>
                            <div><p className="text-xs uppercase tracking-wide text-slate-400">Telepon</p><p className="mt-1 font-medium text-slate-800">{desa.telepon || '-'}</p></div>
                            <div><p className="text-xs uppercase tracking-wide text-slate-400">Email</p><p className="mt-1 font-medium text-slate-800">{desa.email || '-'}</p></div>
                            <div><p className="text-xs uppercase tracking-wide text-slate-400">Status</p><p className="mt-1 font-medium text-slate-800">{desa.aktif ? 'Aktif' : 'Nonaktif'}</p></div>
                        </div>
                    </div>
                )}
            </section>
        </VillageDashboardLayout>
    );
}
