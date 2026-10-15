import { Head, useForm, usePage } from '@inertiajs/react';
import { ArrowRight, Eye, EyeOff, LockKeyhole, UserRound } from 'lucide-react';
import { useState } from 'react';

export default function Login() {
    const { desa } = usePage().props;
    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        login: '',
        password: '',
        remember: false,
    });

    const villageName = desa?.nama_desa || 'Desa Bojo';
    const logoUrl = desa?.logo ? `/${desa.logo}` : '/logo-desa-bojo.svg';

    const handleSubmit = (event) => {
        event.preventDefault();
        post('/login');
    };

    return (
        <>
            <Head title="Masuk" />
            <main className="flex min-h-screen bg-[#f4fbfc]">
                <section className="relative hidden w-[46%] overflow-hidden bg-[#08b4d4] px-12 py-12 text-white lg:flex lg:flex-col lg:justify-between">
                    <div className="absolute -right-24 -top-24 size-80 rounded-full border-[38px] border-white/10" aria-hidden="true" />
                    <div className="absolute -bottom-32 -left-24 size-96 rounded-full border-[52px] border-white/10" aria-hidden="true" />
                    <div className="relative flex items-center gap-3 text-xl font-bold">
                        <img src={logoUrl} alt={`Logo ${villageName}`} className="size-10 rounded-full bg-white object-contain p-1" />
                        {villageName.toUpperCase()}
                    </div>
                    <div className="relative max-w-md">
                        <p className="mb-4 text-sm font-medium uppercase tracking-[0.2em] text-cyan-100">Sistem Informasi Desa</p>
                        <h1 className="text-4xl font-bold leading-tight">Kelola data desa dengan lebih mudah.</h1>
                        <p className="mt-5 text-base leading-7 text-cyan-50">Akses data penduduk, administrasi, dan layanan kantor desa dari satu tempat.</p>
                    </div>
                    <p className="relative text-sm text-cyan-100">© 2026 Pemerintah {villageName}</p>
                </section>

                <section className="flex flex-1 items-center justify-center px-5 py-10 sm:px-10">
                    <div className="w-full max-w-md">
                        <div className="mb-8 lg:hidden">
                            <div className="flex items-center gap-2 text-xl font-bold text-[#079fbd]">
                                <img src={logoUrl} alt={`Logo ${villageName}`} className="size-8 rounded-full bg-white object-contain p-1" />
                                {villageName.toUpperCase()}
                            </div>
                        </div>
                        <div className="mb-8">
                            <p className="text-sm font-medium text-[#079fbd]">Selamat datang kembali</p>
                            <h2 className="mt-2 text-3xl font-bold tracking-tight text-slate-800">Masuk ke akun Anda</h2>
                            <p className="mt-2 text-sm text-slate-500">Gunakan akun perangkat desa untuk melanjutkan.</p>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-5">
                            <label className="block">
                                <span className="mb-2 block text-sm font-medium text-slate-700">Email atau Username</span>
                                <span className="relative block">
                                    <UserRound className="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-slate-400" aria-hidden="true" />
                                    <input type="text" name="login" value={data.login} onChange={(event) => setData('login', event.target.value)} required autoComplete="username" placeholder="Masukkan email atau username" className="h-12 w-full rounded-lg border border-slate-200 bg-white pl-11 pr-4 text-sm text-slate-800 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100" />
                                </span>
                                {errors.login && <p className="mt-1 text-xs text-red-600">{errors.login}</p>}
                            </label>
                            <label className="block">
                                <span className="mb-2 block text-sm font-medium text-slate-700">Password</span>
                                <span className="relative block">
                                    <LockKeyhole className="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-slate-400" aria-hidden="true" />
                                    <input type={showPassword ? 'text' : 'password'} name="password" value={data.password} onChange={(event) => setData('password', event.target.value)} required autoComplete="current-password" placeholder="Masukkan password" className="h-12 w-full rounded-lg border border-slate-200 bg-white pl-11 pr-12 text-sm text-slate-800 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100" />
                                    <button type="button" onClick={() => setShowPassword((visible) => !visible)} className="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-700" aria-label={showPassword ? 'Sembunyikan password' : 'Tampilkan password'}>
                                        {showPassword ? <EyeOff className="size-5" /> : <Eye className="size-5" />}
                                    </button>
                                </span>
                                {errors.password && <p className="mt-1 text-xs text-red-600">{errors.password}</p>}
                            </label>
                            <div className="flex items-center justify-between gap-4 text-sm">
                                <label className="flex items-center gap-2 text-slate-600">
                                    <input id="remember" name="remember" type="checkbox" checked={data.remember} onChange={(event) => setData('remember', event.target.checked)} className="size-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500" />
                                    <span><span className="block font-medium text-slate-700">Ingat saya</span><span className="block text-xs text-slate-400">Tetap masuk setelah browser ditutup.</span></span>
                                </label>
                                <button type="button" className="font-medium text-[#079fbd] hover:text-[#067f98]">Lupa password?</button>
                            </div>
                            <button type="submit" disabled={processing} className="flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-[#08a8c8] text-sm font-semibold text-white shadow-sm transition hover:bg-[#078fae] focus:outline-none focus:ring-2 focus:ring-cyan-300 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70">
                                {processing ? 'Memproses...' : 'Masuk ke dashboard'}
                                <ArrowRight className="size-4" />
                            </button>
                        </form>
                        <p className="mt-8 text-center text-xs text-slate-400">Hubungi administrator desa jika Anda belum memiliki akun.</p>
                    </div>
                </section>
            </main>
        </>
    );
}
