import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Camera, Eye, EyeOff, Save, ShieldCheck, UserRound } from 'lucide-react';
import { useState } from 'react';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

export default function ProfilePage() {
    const { auth } = usePage().props;
    const user = auth?.user;
    const [preview, setPreview] = useState(user?.avatar ? `/storage/${user.avatar}` : '');
    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        _method:"put",
        name: user?.name || '',
        email: user?.email || '',
        current_password: '',
        password: '',
        password_confirmation: '',
        avatar: null,
    });

    const handleAvatarChange = (event) => {
        const file = event.target.files?.[0];

        if (!file) {
            setData('avatar', null);
            setPreview(user?.avatar ? `/storage/${user.avatar}` : '');
            event.target.value = '';
            return;
        }

        setData('avatar', file);
        setPreview(URL.createObjectURL(file));
    };

    const submit = (event) => {
        event.preventDefault();

        const formData = new FormData();

        formData.append('_method', 'put');
        formData.append('name', data.name ?? '');
        formData.append('email', data.email ?? '');

        if (data.current_password) {
            formData.append('current_password', data.current_password);
        }

        if (data.password) {
            formData.append('password', data.password);
            formData.append('password_confirmation', data.password_confirmation ?? '');
        }

        if (data.avatar instanceof File || data.avatar instanceof Blob) {
            formData.append('avatar', data.avatar);
        }

        post('/settings/profile', {
            data: formData,
            forceFormData: true,
        });
    };

    return (
        <VillageDashboardLayout>
            <Head title="Update Profile" />
            <section className="mx-auto max-w-4xl space-y-5">
                <div className="flex items-center gap-3">
                    <Link href="/dashboard" className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600">
                        <ArrowLeft className="size-5" />
                    </Link>
                    <div>
                        <p className="text-sm font-medium text-cyan-600">Pengaturan</p>
                        <h1 className="text-2xl font-bold text-slate-800">Update Profile</h1>
                    </div>
                </div>

                <form onSubmit={submit} encType="multipart/form-data" className="space-y-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <div className="flex items-center justify-between border-b border-slate-100 pb-5">
                        <div className="flex items-center gap-3">
                            <div className="flex size-12 items-center justify-center rounded-full bg-cyan-50 text-cyan-600">
                                <UserRound className="size-6" />
                            </div>
                            <div>
                                <h2 className="font-semibold text-slate-800">Profil pengguna</h2>
                                <p className="text-sm text-slate-500">Perbarui data diri dan foto profil Anda.</p>
                            </div>
                        </div>
                        <div className="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                            <ShieldCheck className="size-3.5" />
                            Akun aktif
                        </div>
                    </div>

                    <div className="grid gap-6 md:grid-cols-[220px_1fr]">
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <label className="block cursor-pointer">
                                <span className="mb-3 block text-sm font-medium text-slate-700">Avatar</span>
                                <div className="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-slate-300 bg-white p-4">
                                    {preview ? (
                                        <img src={preview} alt="Preview avatar" className="size-24 rounded-full border border-slate-200 object-cover" />
                                    ) : (
                                        <div className="flex size-24 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                            <Camera className="size-8" />
                                        </div>
                                    )}
                                    <span className="text-center text-xs text-slate-500">Klik untuk upload foto</span>
                                </div>
                                <input type="file" accept="image/*" onChange={handleAvatarChange} className="hidden" />
                            </label>
                            {errors.avatar && <p className="mt-2 text-xs text-red-600">{errors.avatar}</p>}
                        </div>

                        <div className="grid gap-5">
                            <label className="block">
                                <span className="mb-2 block text-sm font-medium text-slate-700">Nama lengkap</span>
                                <input
                                    value={data.name}
                                    onChange={(event) => setData('name', event.target.value)}
                                    className={`h-11 w-full rounded-md border px-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100 ${errors.name ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-100' : 'border-slate-200 bg-white'}`}
                                />
                                {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name}</p>}
                            </label>

                            <label className="block">
                                <span className="mb-2 block text-sm font-medium text-slate-700">Email</span>
                                <input
                                    type="email"
                                    value={data.email}
                                    onChange={(event) => setData('email', event.target.value)}
                                    className={`h-11 w-full rounded-md border px-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100 ${errors.email ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-100' : 'border-slate-200 bg-white'}`}
                                />
                                {errors.email && <p className="mt-1 text-xs text-red-600">{errors.email}</p>}
                            </label>

                            <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div className="mb-3 flex items-center justify-between gap-3">
                                    <div>
                                        <h3 className="font-semibold text-slate-800">Ganti password</h3>
                                        <p className="text-sm text-slate-500">Kosongkan jika tidak ingin mengubah password.</p>
                                    </div>
                                    <button type="button" onClick={() => setShowPassword((value) => !value)} className="text-sm font-medium text-cyan-700 hover:text-cyan-800">
                                        {showPassword ? 'Sembunyikan' : 'Tampilkan'}
                                    </button>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <label className="block md:col-span-2">
                                        <span className="mb-2 block text-sm font-medium text-slate-700">Password saat ini</span>
                                        <div className="relative">
                                            <input
                                                type={showPassword ? 'text' : 'password'}
                                                value={data.current_password}
                                                onChange={(event) => setData('current_password', event.target.value)}
                                                className={`h-11 w-full rounded-md border px-3 pr-11 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100 ${errors.current_password ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-100' : 'border-slate-200 bg-white'}`}
                                                placeholder="Masukkan password lama"
                                            />
                                            <button type="button" onClick={() => setShowPassword((value) => !value)} className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400" aria-label="Tampilkan password">
                                                {showPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                                            </button>
                                        </div>
                                        {errors.current_password && <p className="mt-1 text-xs text-red-600">{errors.current_password}</p>}
                                    </label>

                                    <label className="block">
                                        <span className="mb-2 block text-sm font-medium text-slate-700">Password baru</span>
                                        <input
                                            type={showPassword ? 'text' : 'password'}
                                            value={data.password}
                                            onChange={(event) => setData('password', event.target.value)}
                                            className={`h-11 w-full rounded-md border px-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100 ${errors.password ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-100' : 'border-slate-200 bg-white'}`}
                                            placeholder="Minimal 8 karakter"
                                        />
                                        {errors.password && <p className="mt-1 text-xs text-red-600">{errors.password}</p>}
                                    </label>

                                    <label className="block">
                                        <span className="mb-2 block text-sm font-medium text-slate-700">Konfirmasi password</span>
                                        <input
                                            type={showPassword ? 'text' : 'password'}
                                            value={data.password_confirmation}
                                            onChange={(event) => setData('password_confirmation', event.target.value)}
                                            className={`h-11 w-full rounded-md border px-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100 ${errors.password_confirmation ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-100' : 'border-slate-200 bg-white'}`}
                                            placeholder="Ulangi password baru"
                                        />
                                        {errors.password_confirmation && <p className="mt-1 text-xs text-red-600">{errors.password_confirmation}</p>}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <Link href="/dashboard" className="rounded-md border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</Link>
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-700 disabled:opacity-60"
                        >
                            <Save className="size-4" />
                            {processing ? 'Menyimpan...' : 'Simpan Profil'}
                        </button>
                    </div>
                </form>
            </section>
        </VillageDashboardLayout>
    );
}
