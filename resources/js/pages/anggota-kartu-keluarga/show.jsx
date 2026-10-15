import { Head } from '@inertiajs/react';
import Link from '@/components/permission-link';
import { ArrowLeft, Users, Pencil } from 'lucide-react';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

export default function AnggotaKartuKeluargaShow({ anggotaKartuKeluarga }) {
    return (
        <VillageDashboardLayout>
            <Head title={`Detail Anggota Keluarga ${anggotaKartuKeluarga.penduduk?.nama_lengkap || ''}`} />
            <section className="mx-auto max-w-8xl space-y-5">
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <Link href="/anggota-kartu-keluarga" className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link>
                        <div>
                            <p className="text-sm font-medium text-cyan-600">Kependudukan</p>
                            <h1 className="text-2xl font-bold text-slate-800">Detail Anggota Keluarga</h1>
                        </div>
                    </div>
                    <Link href={`/anggota-kartu-keluarga/${anggotaKartuKeluarga.id}/edit`} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-700"><Pencil className="size-4" /> Edit</Link>
                </div>

                <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center gap-4 bg-gradient-to-r from-cyan-600 to-cyan-500 p-6 text-white">
                        <div className="flex size-16 items-center justify-center rounded-full bg-white/20"><Users className="size-8" /></div>
                        <div>
                            <h2 className="text-xl font-bold">{anggotaKartuKeluarga.penduduk?.nama_lengkap || '-'}</h2>
                            <p className="text-sm text-cyan-100">{anggotaKartuKeluarga.hubungan_keluarga || '-'}</p>
                        </div>
                    </div>

                    <div className="grid gap-5 p-6 md:grid-cols-2">
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Kartu Keluarga</p><p className="mt-1 font-medium text-slate-800">{anggotaKartuKeluarga.kartu_keluarga?.nomor_kk || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Penduduk</p><p className="mt-1 font-medium text-slate-800">{anggotaKartuKeluarga.penduduk?.nama_lengkap || '-'} ({anggotaKartuKeluarga.penduduk?.nik || '-'})</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Hubungan Keluarga</p><p className="mt-1 font-medium text-slate-800">{anggotaKartuKeluarga.hubungan_keluarga || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Status Aktif</p><p className="mt-1 font-medium text-slate-800">{anggotaKartuKeluarga.aktif ? 'Aktif' : 'Tidak Aktif'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Tanggal Masuk</p><p className="mt-1 font-medium text-slate-800">{anggotaKartuKeluarga.tanggal_masuk || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Tanggal Keluar</p><p className="mt-1 font-medium text-slate-800">{anggotaKartuKeluarga.tanggal_keluar || '-'}</p></div>
                        <div className="md:col-span-2"><p className="text-xs uppercase tracking-wide text-slate-400">Alasan Keluar</p><p className="mt-1 font-medium text-slate-800">{anggotaKartuKeluarga.alasan_keluar || '-'}</p></div>
                    </div>
                </div>
            </section>
        </VillageDashboardLayout>
    );
}
