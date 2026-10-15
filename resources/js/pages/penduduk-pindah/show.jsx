import { Head } from '@inertiajs/react';
import Link from '@/components/permission-link';
import { ArrowLeft, Map, Pencil } from 'lucide-react';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

export default function PendudukPindahShow({ pendudukPindah, perpindahan }) {
    if (perpindahan) {
        return (
            <VillageDashboardLayout>
                <Head title="Detail Perpindahan Penduduk" />
                <section className="mx-auto max-w-5xl space-y-5">
                    <Link href="/penduduk-pindah" className="inline-flex items-center gap-2 text-sm text-slate-500"><ArrowLeft className="size-4" /> Kembali</Link>
                    <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h1 className="text-2xl font-bold text-slate-800">Detail Perpindahan Penduduk</h1>
                        <div className="mt-5 grid gap-4 text-sm md:grid-cols-2"><p><b>Jenis:</b> {perpindahan.jenis_pindah}</p><p><b>Tujuan:</b> {perpindahan.tujuan_pindah === 'dalam_desa' ? 'Dalam Desa' : 'Keluar Desa'}</p><p><b>Tanggal:</b> {perpindahan.tanggal_pindah}</p><p><b>Nomor Surat:</b> {perpindahan.nomor_surat_pindah || '-'}</p><p className="md:col-span-2"><b>Alamat Tujuan:</b> {perpindahan.alamat_tujuan || '-'}</p></div>
                        <h2 className="mt-8 mb-3 text-lg font-semibold text-slate-800">Anggota yang Pindah</h2>
                        <div className="overflow-x-auto"><table className="w-full text-left text-sm"><thead><tr className="border-b text-slate-500"><th className="p-2">NIK</th><th className="p-2">Nama</th><th className="p-2">Status</th></tr></thead><tbody>{perpindahan.anggota_perpindahan?.map((item) => <tr key={item.id} className="border-b"><td className="p-2">{item.penduduk?.nik || '-'}</td><td className="p-2">{item.penduduk?.nama_lengkap || '-'}</td><td className="p-2">{item.penduduk?.status_penduduk || '-'}</td></tr>)}</tbody></table></div>
                    </div>
                </section>
            </VillageDashboardLayout>
        );
    }
    return (
        <VillageDashboardLayout>
            <Head title={`Detail Penduduk Keluar ${pendudukPindah.penduduk?.nama_lengkap || ''}`} />
            <section className="mx-auto max-w-8xl space-y-5">
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <Link href="/penduduk-pindah" className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link>
                        <div>
                            <p className="text-sm font-medium text-cyan-600">Peristiwa Kependudukan</p>
                            <h1 className="text-2xl font-bold text-slate-800">Detail Penduduk Keluar</h1>
                        </div>
                    </div>
                    <Link href={`/penduduk-pindah/${pendudukPindah.id}/edit`} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-700"><Pencil className="size-4" /> Edit</Link>
                </div>

                <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center gap-4 bg-gradient-to-r from-cyan-600 to-cyan-500 p-6 text-white">
                        <div className="flex size-16 items-center justify-center rounded-full bg-white/20"><Map className="size-8" /></div>
                        <div>
                            <h2 className="text-xl font-bold">{pendudukPindah.penduduk?.nama_lengkap || '-'}</h2>
                            <p className="text-sm text-cyan-100">{pendudukPindah.status || 'Status belum ditentukan'}</p>
                        </div>
                    </div>

                    <div className="grid gap-5 p-6 md:grid-cols-2">
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Penduduk</p><p className="mt-1 font-medium text-slate-800">{pendudukPindah.penduduk?.nama_lengkap || '-'} ({pendudukPindah.penduduk?.nik || '-'})</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Tanggal Pindah</p><p className="mt-1 font-medium text-slate-800">{pendudukPindah.tanggal_pindah || '-'}</p></div>
                        <div className="md:col-span-2"><p className="text-xs uppercase tracking-wide text-slate-400">Alamat Tujuan</p><p className="mt-1 font-medium text-slate-800">{pendudukPindah.alamat_tujuan || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Tujuan</p><p className="mt-1 font-medium text-slate-800">{[pendudukPindah.desa_tujuan, pendudukPindah.kecamatan_tujuan, pendudukPindah.kabupaten_tujuan, pendudukPindah.provinsi_tujuan].filter(Boolean).join(', ') || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Nomor Surat Pindah</p><p className="mt-1 font-medium text-slate-800">{pendudukPindah.nomor_surat_pindah || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Alasan</p><p className="mt-1 font-medium text-slate-800">{pendudukPindah.alasan || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Status</p><p className="mt-1 font-medium text-slate-800">{pendudukPindah.status || '-'}</p></div>
                        <div className="md:col-span-2"><p className="text-xs uppercase tracking-wide text-slate-400">Catatan</p><p className="mt-1 font-medium text-slate-800">{pendudukPindah.catatan || '-'}</p></div>
                    </div>
                </div>
            </section>
        </VillageDashboardLayout>
    );
}
