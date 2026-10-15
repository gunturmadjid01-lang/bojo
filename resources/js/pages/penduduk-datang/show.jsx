import { Head } from '@inertiajs/react';
import Link from '@/components/permission-link';
import { ArrowLeft, MapPinned, Pencil } from 'lucide-react';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

export default function PendudukDatangShow({ pendudukDatang }) {
    const anggota = pendudukDatang.anggota_kedatangan || pendudukDatang.anggota || [];
    const anggotaKk = pendudukDatang.kartu_keluarga_tujuan?.anggota_keluarga || pendudukDatang.anggota_kk || [];
    const asal = [pendudukDatang.desa_sebelumnya, pendudukDatang.kecamatan_sebelumnya, pendudukDatang.kabupaten_sebelumnya, pendudukDatang.provinsi_sebelumnya].filter(Boolean).join(', ') || '-';
    return (
        <VillageDashboardLayout>
            <Head title={`Detail Penduduk Masuk ${pendudukDatang.penduduk?.nama_lengkap || ''}`} />
            <section className="mx-auto max-w-8xl space-y-5">
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <Link href="/penduduk-datang" className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link>
                        <div>
                            <p className="text-sm font-medium text-cyan-600">Peristiwa Kependudukan</p>
                            <h1 className="text-2xl font-bold text-slate-800">Detail Penduduk Masuk</h1>
                        </div>
                    </div>
                    <Link href={`/penduduk-datang/${pendudukDatang.id}/edit`} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-700"><Pencil className="size-4" /> Edit</Link>
                </div>

                <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center gap-4 bg-gradient-to-r from-cyan-600 to-cyan-500 p-6 text-white">
                        <div className="flex size-16 items-center justify-center rounded-full bg-white/20"><MapPinned className="size-8" /></div>
                        <div>
                            <h2 className="text-xl font-bold">{pendudukDatang.penduduk?.nama_lengkap || '-'}</h2>
                            <p className="text-sm text-cyan-100">{pendudukDatang.status || 'Status belum ditentukan'}</p>
                        </div>
                    </div>

                    <div className="grid gap-5 p-6 md:grid-cols-2">
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Jumlah Penduduk</p><p className="mt-1 font-medium text-slate-800">{anggota.length || (pendudukDatang.penduduk ? 1 : 0)} orang</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Tanggal Datang</p><p className="mt-1 font-medium text-slate-800">{pendudukDatang.tanggal_datang || '-'}</p></div>
                        <div className="md:col-span-2"><p className="text-xs uppercase tracking-wide text-slate-400">Alamat Sebelumnya</p><p className="mt-1 font-medium text-slate-800">{pendudukDatang.alamat_sebelumnya || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Asal</p><p className="mt-1 font-medium text-slate-800">{asal}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Kartu Keluarga Tujuan</p><p className="mt-1 font-medium text-slate-800">{pendudukDatang.kartu_keluarga_tujuan?.nomor_kk || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Nomor Surat Pindah</p><p className="mt-1 font-medium text-slate-800">{pendudukDatang.nomor_surat_pindah || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Alasan</p><p className="mt-1 font-medium text-slate-800">{pendudukDatang.alasan || '-'}</p></div>
                        <div><p className="text-xs uppercase tracking-wide text-slate-400">Status</p><p className="mt-1 font-medium text-slate-800">{pendudukDatang.status || '-'}</p></div>
                        <div className="md:col-span-2"><p className="text-xs uppercase tracking-wide text-slate-400">Catatan</p><p className="mt-1 font-medium text-slate-800">{pendudukDatang.catatan || '-'}</p></div>
                    </div>
                </div>

                <div className="grid gap-5 lg:grid-cols-2">
                    <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="mb-4 flex items-center justify-between"><h2 className="text-lg font-bold text-slate-800">Penduduk Masuk</h2></div>
                        <div className="space-y-3">{anggota.length ? anggota.map((member) => <div key={member.id} className="flex items-center justify-between gap-3 rounded-md border border-slate-200 bg-slate-50 p-3"><div><p className="font-semibold text-slate-800">{member.penduduk?.nama_lengkap || '-'}</p><p className="text-sm text-slate-500">NIK {member.penduduk?.nik || '-'} · {member.penduduk?.jenis_kelamin || '-'}</p></div><span className="text-sm text-slate-600">{member.hubungan_keluarga || '-'}</span></div>) : <p className="text-sm text-slate-500">Data anggota belum tersedia.</p>}</div>
                    </div>
                    <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="mb-4 flex items-center justify-between"><h2 className="text-lg font-bold text-slate-800">Kartu Keluarga Tujuan</h2><span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{pendudukDatang.kartu_keluarga_tujuan?.nomor_kk || 'Belum terhubung'}</span></div>
                        <div className="space-y-3">{[['Alamat', pendudukDatang.kartu_keluarga_tujuan?.alamat], ['Anggota KK', `${anggotaKk.length} orang`], ['Status Relasi', pendudukDatang.kartu_keluarga_tujuan_id ? 'Terhubung' : 'Belum terhubung']].map(([label, value]) => <div key={label} className="flex justify-between gap-3 border-b border-slate-100 pb-3 text-sm"><span className="text-slate-500">{label}</span><span className="text-right font-medium text-slate-800">{value || '-'}</span></div>)}</div>
                    </div>
                </div>
            </section>
        </VillageDashboardLayout>
    );
}
