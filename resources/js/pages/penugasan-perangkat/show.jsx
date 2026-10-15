import { Head, Link } from '@inertiajs/react';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

const formatTanggal = (value) => {
    if (!value) return '-';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }).format(date);
};

export default function Show({ assignment }) {
    const fields = [['Nama pejabat', assignment.perangkat_desa?.nama_lengkap || assignment.perangkat_desa?.user?.name], ['Username', assignment.perangkat_desa?.user?.username], ['Jabatan', assignment.jabatan_desa?.nama], ['Tingkat jabatan', assignment.jabatan_desa?.tingkat], ['Wilayah', assignment.dusun?.nama || assignment.desa?.nama_desa], ['Tanggal mulai', formatTanggal(assignment.tanggal_mulai)], ['Tanggal selesai', formatTanggal(assignment.tanggal_selesai)], ['Nomor SK pengangkatan', assignment.nomor_sk_pengangkatan || '-'], ['Tanggal SK pengangkatan', formatTanggal(assignment.tanggal_sk_pengangkatan)], ['Nomor SK pemberhentian', assignment.nomor_sk_pemberhentian || '-'], ['Tanggal SK pemberhentian', formatTanggal(assignment.tanggal_sk_pemberhentian)], ['Status', assignment.status], ['Catatan', assignment.catatan || '-']];
    return <VillageDashboardLayout><Head title="Detail Penugasan" /><section className="mx-auto max-w-4xl space-y-5"><div className="flex items-center justify-between"><h1 className="text-2xl font-bold text-slate-800">Detail Penugasan</h1><Link href="/penugasan-perangkat" className="rounded-md border border-slate-200 px-4 py-2 text-sm">Kembali</Link></div><div className="grid gap-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-2">{fields.map(([label, value]) => <div key={label}><p className="text-xs uppercase tracking-wide text-slate-400">{label}</p><p className="mt-1 font-medium text-slate-800">{value}</p></div>)}</div></section></VillageDashboardLayout>;
}
