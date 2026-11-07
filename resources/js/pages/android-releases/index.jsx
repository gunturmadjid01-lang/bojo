import VillageDashboardLayout from '@/layouts/village-dashboard-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { AlertCircle, ArrowDownToLine, Upload } from 'lucide-react';
import { useState } from 'react';

function formatBytes(bytes) {
    if (!bytes) return '—';
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function AndroidReleasesIndex({ releases = [], downloadUrl }) {
    const [submitError, setSubmitError] = useState('');
    const { data, setData, post, processing, progress, errors, reset } = useForm({ version: '', version_code: '', release_notes: '', apk: null });
    const errorMessages = [...Object.values(errors || {}).flatMap((error) => Array.isArray(error) ? error : [error]).filter(Boolean), ...(submitError ? [submitError] : [])];
    const submit = (event) => {
        event.preventDefault();
        setSubmitError('');
        post(route('android-releases.store'), {
            forceFormData: true,
            onSuccess: () => reset(),
            onHttpException: (response) => setSubmitError(`Server menolak unggahan (HTTP ${response.status}). Periksa batas ukuran unggahan APK di hosting.`),
            onNetworkError: () => setSubmitError('Koneksi terputus saat mengunggah APK. Periksa koneksi dan coba lagi.'),
        });
    };

    return (
        <VillageDashboardLayout>
            <Head title="Rilis Aplikasi Android" />
            <div className="mx-auto max-w-5xl space-y-6">
                <header className="flex flex-wrap items-start justify-between gap-4">
                    <div><p className="text-sm font-medium text-cyan-700">Pengaturan Sistem</p><h1 className="mt-1 text-2xl font-bold">Rilis Aplikasi Android</h1><p className="mt-2 text-sm text-slate-500">Unggah APK native terbaru untuk halaman download publik.</p></div>
                    <Link href={downloadUrl} className="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"><ArrowDownToLine className="size-4" /> Lihat halaman download</Link>
                </header>

                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <h2 className="text-lg font-semibold">Publikasikan APK baru</h2>
                    <p className="mt-1 text-sm text-slate-500">Kode build harus selalu lebih besar dari rilis sebelumnya. Rilis lama tetap tersimpan dalam riwayat.</p>
                    {errorMessages.length ? <div role="alert" aria-live="assertive" className="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800"><div className="flex items-start gap-2"><AlertCircle className="mt-0.5 size-4 shrink-0" /><div><p className="font-semibold">Rilis belum berhasil dipublikasikan</p><ul className="mt-1 list-inside list-disc">{errorMessages.map((message, index) => <li key={`${index}-${message}`}>{message}</li>)}</ul></div></div></div> : null}
                    <form onSubmit={submit} className="mt-6 grid gap-5 sm:grid-cols-2">
                        <label className="block"><span className="mb-1.5 block text-sm font-medium">Versi aplikasi</span><input value={data.version} onChange={(event) => setData('version', event.target.value)} placeholder="1.1.0" className="h-11 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-100" />{errors.version ? <span className="mt-1 block text-sm text-red-600">{errors.version}</span> : null}</label>
                        <label className="block"><span className="mb-1.5 block text-sm font-medium">Kode build Android</span><input type="number" min="1" value={data.version_code} onChange={(event) => setData('version_code', event.target.value)} placeholder="Contoh: 12" className="h-11 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-100" />{errors.version_code ? <span className="mt-1 block text-sm text-red-600">{errors.version_code}</span> : null}</label>
                        <label className="block sm:col-span-2"><span className="mb-1.5 block text-sm font-medium">Catatan pembaruan</span><textarea rows="4" value={data.release_notes} onChange={(event) => setData('release_notes', event.target.value)} placeholder="Perbaikan dan pembaruan pada versi ini" className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-100" />{errors.release_notes ? <span className="mt-1 block text-sm text-red-600">{errors.release_notes}</span> : null}</label>
                        <label className="block sm:col-span-2"><span className="mb-1.5 block text-sm font-medium">File APK (maksimal 500 MB)</span><input type="file" accept=".apk,application/vnd.android.package-archive" onChange={(event) => setData('apk', event.target.files?.[0] || null)} className="block w-full rounded-lg border border-slate-300 bg-white text-sm file:mr-4 file:border-0 file:bg-cyan-50 file:px-4 file:py-3 file:font-semibold file:text-cyan-800" />{errors.apk ? <span className="mt-1 block text-sm text-red-600">{errors.apk}</span> : null}</label>
                        {progress ? <div className="sm:col-span-2"><div className="mb-1 flex justify-between text-xs text-slate-500"><span>Mengunggah APK</span><span>{progress.percentage}%</span></div><div className="h-2 overflow-hidden rounded-full bg-slate-100"><div className="h-full bg-cyan-600 transition-all" style={{ width: `${progress.percentage}%` }} /></div></div> : null}
                        <div className="sm:col-span-2"><button disabled={processing} className="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-cyan-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-cyan-800 disabled:cursor-not-allowed disabled:opacity-60"><Upload className="size-4" />{processing ? 'Memublikasikan...' : 'Publikasikan versi'}</button></div>
                    </form>
                </section>

                <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 px-5 py-4"><h2 className="font-semibold">Riwayat rilis</h2></div>
                    {releases.length ? <div className="overflow-x-auto"><table className="w-full min-w-[620px] text-left text-sm"><thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th className="px-5 py-3">Versi</th><th className="px-5 py-3">Build</th><th className="px-5 py-3">Ukuran</th><th className="px-5 py-3">Tanggal</th><th className="px-5 py-3">Status</th></tr></thead><tbody className="divide-y divide-slate-100">{releases.map((release) => <tr key={release.id}><td className="px-5 py-4 font-semibold">{release.version}</td><td className="px-5 py-4">{release.version_code}</td><td className="px-5 py-4">{formatBytes(release.file_size)}</td><td className="px-5 py-4">{new Date(release.published_at).toLocaleDateString('id-ID')}</td><td className="px-5 py-4">{release.is_latest ? <span className="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Terbaru</span> : <span className="text-slate-500">Riwayat</span>}</td></tr>)}</tbody></table></div> : <p className="px-5 py-8 text-center text-sm text-slate-500">Belum ada APK yang dipublikasikan.</p>}
                </section>
            </div>
        </VillageDashboardLayout>
    );
}
