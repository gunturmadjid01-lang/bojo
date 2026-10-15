import VillageDashboardLayout from '@/layouts/village-dashboard-layout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, Clock3, PlayCircle, XCircle } from 'lucide-react';
import Select from 'react-select';
import { canAccessHref } from '@/lib/permissions';

const statusMeta = {
    menunggu_verifikasi: { label: 'Menunggu Verifikasi', className: 'bg-amber-100 text-amber-700', icon: Clock3 },
    proses_verifikasi: { label: 'Proses Verifikasi', className: 'bg-cyan-100 text-cyan-700', icon: PlayCircle },
    layak: { label: 'Layak', className: 'bg-emerald-100 text-emerald-700', icon: CheckCircle2 },
    tidak_layak: { label: 'Tidak Layak', className: 'bg-red-100 text-red-700', icon: XCircle },
};

const outcomeOptions = [
    { value: 'layak', label: 'Layak' },
    { value: 'tidak_layak', label: 'Tidak Layak' },
];

const formatDate = (value, includeTime = false) => {
    if (!value) return '-';
    return new Date(value).toLocaleString(
        'id-ID',
        includeTime
            ? { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }
            : { day: '2-digit', month: 'long', year: 'numeric' },
    );
};

export default function Show({ penerima }) {
    const canEdit = canAccessHref(usePage().props?.auth?.user, `/penerima-bantuan/${penerima.id}`, 'edit');
    const { data, setData, post, processing, errors } = useForm({ status_kelayakan: 'layak', catatan_verifikasi: '' });
    const status = statusMeta[penerima.status_alur] || statusMeta.menunggu_verifikasi;
    const StatusIcon = status.icon;
    const recipientName = penerima.penduduk?.nama_lengkap || `KK ${penerima.kartu_keluarga?.nomor_kk}`;

    const startVerification = () => {
        if (window.confirm(`Mulai proses verifikasi untuk ${recipientName}?`)) {
            router.post(`/penerima-bantuan/${penerima.id}/mulai-verifikasi`, {}, { preserveScroll: true });
        }
    };

    const finishVerification = (event) => {
        event.preventDefault();
        post(`/penerima-bantuan/${penerima.id}/hasil-verifikasi`, { preserveScroll: true });
    };

    return (
        <VillageDashboardLayout>
            <Head title="Detail Penerima Bantuan" />
            <section className="mx-auto max-w-4xl space-y-5">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p className="text-sm font-medium text-cyan-600">Bantuan Sosial</p>
                        <h1 className="mt-1 text-2xl font-bold text-slate-800">Detail Penerima Bantuan</h1>
                    </div>
                    <span className={`inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold ${status.className}`}>
                        <StatusIcon className="size-4" /> {status.label}
                    </span>
                </div>

                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <dl className="grid gap-5 sm:grid-cols-2">
                        {[
                            ['Program', penerima.program?.nama],
                            ['Penerima', recipientName],
                            [penerima.penduduk ? 'NIK' : 'Nomor KK', penerima.penduduk?.nik || penerima.kartu_keluarga?.nomor_kk],
                            ['Tanggal Terdaftar', formatDate(penerima.tanggal_terdaftar)],
                            ['Catatan Pendaftaran', penerima.catatan || '-'],
                            ['Diverifikasi Oleh', penerima.verifikator?.name || '-'],
                            ['Tanggal Verifikasi', formatDate(penerima.tanggal_verifikasi, true)],
                            ['Catatan Verifikasi', penerima.catatan_verifikasi || '-'],
                        ].map(([label, value]) => (
                            <div key={label}>
                                <dt className="text-xs font-semibold tracking-wide text-slate-400 uppercase">{label}</dt>
                                <dd className="mt-1 font-medium text-slate-800">{value || '-'}</dd>
                            </div>
                        ))}
                    </dl>
                </div>

                {canEdit && penerima.status_alur === 'menunggu_verifikasi' && (
                    <div className="rounded-xl border border-amber-200 bg-amber-50 p-5">
                        <h2 className="font-semibold text-slate-800">Menunggu pemeriksaan</h2>
                        <p className="mt-1 text-sm text-slate-600">Mulai verifikasi setelah dokumen dan kondisi calon penerima siap diperiksa.</p>
                        <button
                            type="button"
                            onClick={startVerification}
                            className="mt-4 inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-700"
                        >
                            <PlayCircle className="size-4" /> Mulai Verifikasi
                        </button>
                    </div>
                )}

                {canEdit && penerima.status_alur === 'proses_verifikasi' && (
                    <form onSubmit={finishVerification} className="space-y-4 rounded-xl border border-cyan-200 bg-cyan-50 p-5">
                        <div>
                            <h2 className="font-semibold text-slate-800">Tetapkan hasil verifikasi</h2>
                            <p className="mt-1 text-sm text-slate-600">Pilih hasil setelah pemeriksaan penerima selesai.</p>
                        </div>
                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Hasil Kelayakan *</span>
                            <Select
                                options={outcomeOptions}
                                value={outcomeOptions.find((item) => item.value === data.status_kelayakan)}
                                onChange={(item) => setData('status_kelayakan', item.value)}
                                isSearchable={false}
                            />
                            {errors.status_kelayakan && <p className="mt-1 text-xs text-red-600">{errors.status_kelayakan}</p>}
                        </label>
                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">
                                Catatan Verifikasi {data.status_kelayakan === 'tidak_layak' && '*'}
                            </span>
                            <textarea
                                value={data.catatan_verifikasi}
                                onChange={(event) => setData('catatan_verifikasi', event.target.value)}
                                rows="4"
                                placeholder="Tuliskan hasil pemeriksaan atau alasan tidak layak"
                                className="w-full rounded-md border border-slate-200 bg-white p-3 text-sm outline-none focus:border-cyan-500"
                            />
                            {errors.catatan_verifikasi && <p className="mt-1 text-xs text-red-600">{errors.catatan_verifikasi}</p>}
                        </label>
                        <button
                            disabled={processing}
                            className="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60"
                        >
                            <CheckCircle2 className="size-4" /> {processing ? 'Menyimpan...' : 'Simpan Hasil Verifikasi'}
                        </button>
                    </form>
                )}

                <Link
                    href="/penerima-bantuan"
                    className="inline-flex rounded-md border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Kembali
                </Link>
            </section>
        </VillageDashboardLayout>
    );
}
