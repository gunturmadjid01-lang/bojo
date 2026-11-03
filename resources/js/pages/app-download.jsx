import { Head, Link } from '@inertiajs/react';
import { Download, ShieldCheck, Smartphone } from 'lucide-react';

function formatBytes(bytes) {
    if (!bytes) return null;
    const megabytes = bytes / (1024 * 1024);
    return `${megabytes.toFixed(1)} MB`;
}

export default function AppDownload({ release, canManage = false }) {
    return (
        <>
            <Head title="Unduh Aplikasi Android" />
            <main className="min-h-screen bg-gradient-to-b from-cyan-50 to-slate-50 px-4 py-12 text-slate-800 sm:px-6">
                <div className="mx-auto max-w-2xl">
                    <div className="mb-8 text-center">
                        <div className="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-cyan-600 text-white shadow-lg shadow-cyan-900/15">
                            <Smartphone className="size-8" />
                        </div>
                        <p className="text-sm font-semibold uppercase tracking-[0.16em] text-cyan-700">Sistem Informasi Desa</p>
                        <h1 className="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Aplikasi Android SPDBojo</h1>
                        <p className="mt-3 text-slate-600">Unduh versi resmi terbaru untuk perangkat Android.</p>
                    </div>

                    <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                        {release ? (
                            <>
                                <div className="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <p className="text-sm text-slate-500">Versi terbaru</p>
                                        <p className="mt-1 text-3xl font-bold text-slate-900">{release.version}</p>
                                        <p className="mt-1 text-sm text-slate-500">Build {release.version_code}{release.published_at ? ` · ${new Date(release.published_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}` : ''}</p>
                                    </div>
                                    <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700"><ShieldCheck className="size-4" /> Rilis resmi</span>
                                </div>

                                {release.release_notes ? <div className="mt-6 rounded-xl bg-slate-50 p-4"><h2 className="text-sm font-semibold">Catatan pembaruan</h2><p className="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{release.release_notes}</p></div> : null}

                                <a href={release.file_url} className="mt-6 flex min-h-14 w-full items-center justify-center gap-2 rounded-xl bg-cyan-600 px-5 py-3 font-semibold text-white shadow-sm transition hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2">
                                    <Download className="size-5" /> Unduh APK {release.version}
                                    {formatBytes(release.file_size) ? <span className="text-sm font-normal text-cyan-100">({formatBytes(release.file_size)})</span> : null}
                                </a>
                                <p className="mt-3 break-all text-xs text-slate-400">SHA-256: {release.sha256}</p>
                            </>
                        ) : (
                            <div className="py-8 text-center">
                                <Smartphone className="mx-auto size-10 text-slate-300" />
                                <h2 className="mt-4 text-lg font-semibold">APK belum tersedia</h2>
                                <p className="mt-2 text-sm text-slate-500">Rilis Android akan ditampilkan di halaman ini setelah dipublikasikan.</p>
                            </div>
                        )}

                        <div className="mt-7 border-t border-slate-100 pt-5 text-sm leading-6 text-slate-500">
                            <p>Untuk build native yang lebih baru, buka halaman ini di Android, unduh APK, lalu buka file unduhan dan ikuti petunjuk pemasangan. Pembaruan aplikasi yang kompatibel juga dapat diterima otomatis melalui EAS Update.</p>
                        </div>
                    </section>

                    <div className="mt-6 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm">
                        <Link href={route('login')} className="font-medium text-cyan-700 hover:text-cyan-900">Kembali ke login</Link>
                        {canManage ? <Link href={route('android-releases.index')} className="font-medium text-cyan-700 hover:text-cyan-900">Kelola rilis APK</Link> : null}
                    </div>
                </div>
            </main>
        </>
    );
}
