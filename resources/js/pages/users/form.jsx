import FormSelect from '@/components/form-select';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Eye, EyeOff, ImagePlus, Save, UserRound } from 'lucide-react';
import { useState } from 'react';

export default function UserForm({ user, roles = [] }) {
    const editing = Boolean(user);
    const [showPassword, setShowPassword] = useState(false);
    const [preview, setPreview] = useState(user?.avatar ? `/storage/${user.avatar}` : '');
   const {
    data,
    setData,
    post,
    processing,
    errors,
} = useForm({
    _method: editing ? 'put' : 'post',

    name: user?.name || '',
    username: user?.username || '',
    email: user?.email || '',
    jenis_kelamin: user?.jenis_kelamin || '',
    avatar: null,
    password: '',
    password_confirmation: '',
    role: user?.roles?.[0]?.name || roles[0]?.name || '',
});

    const inputClass = (
        field,
        baseClass = 'h-11 w-full rounded-md border px-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100',
    ) => {
        const errorClass = errors[field] ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-100' : 'border-slate-200 bg-white';
        return `${baseClass} ${errorClass}`;
    };

    const requiredLabel = (label) => (
        <span className="mb-2 flex items-center gap-1 text-sm font-medium text-slate-700">
            <span>{label}</span>
            <span className="text-red-600">*</span>
        </span>
    );

    const optionalLabel = (label) => <span className="mb-2 block text-sm font-medium text-slate-700">{label}</span>;

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
        Object.entries(data).forEach(([key, value]) => {
            if (key === 'avatar') {
                if (value instanceof File || value instanceof Blob) {
                    formData.append('avatar', value);
                }
                return;
            }

            if (value !== null && value !== undefined && value !== '') {
                formData.append(key, value);
            }
        });

        if (editing) {
            post(`/users/${user.id}`, { data: formData, forceFormData: true });
            return;
        }

        post('/users', { data: formData, forceFormData: true });
    };

    const fieldError = (field) => errors[field] && <p className="mt-1 text-xs text-red-600">{errors[field]}</p>;

    return (
        <VillageDashboardLayout>
            <Head title={editing ? 'Edit User' : 'Tambah User'} />
            <section className="max-w-8xl mx-auto space-y-5">
                <div className="flex items-center gap-3">
                    <Link
                        href={editing ? `/users/${user.id}` : '/users'}
                        className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"
                    >
                        <ArrowLeft className="size-5" />
                    </Link>
                    <div>
                        <p className="text-sm font-medium text-cyan-600">Manajemen Akses</p>
                        <h1 className="text-2xl font-bold text-slate-800">{editing ? 'Edit User' : 'Tambah User'}</h1>
                    </div>
                </div>
                <form
                    onSubmit={submit}
                    encType="multipart/form-data"
                    className="space-y-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-7"
                >
                    <div className="flex items-center gap-3 border-b border-slate-100 pb-5">
                        <div className="flex size-11 items-center justify-center rounded-full bg-cyan-50 text-cyan-600">
                            <UserRound className="size-5" />
                        </div>
                        <div>
                            <h2 className="font-semibold text-slate-800">Informasi akun</h2>
                            <p className="text-sm text-slate-500">Isi data pengguna, avatar, dan role aksesnya.</p>
                        </div>
                    </div>
                    <div className="grid gap-5 lg:grid-cols-[220px_1fr]">
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <label className="block cursor-pointer">
                                <span className="mb-3 block text-sm font-medium text-slate-700">Avatar User</span>
                                <div className="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-slate-300 bg-white p-4">
                                    {preview ? (
                                        <img
                                            src={preview}
                                            alt="Preview avatar"
                                            className="size-24 rounded-full border border-slate-200 object-cover"
                                        />
                                    ) : (
                                        <div className="flex size-24 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                            <ImagePlus className="size-8" />
                                        </div>
                                    )}
                                    <span className="text-center text-xs text-slate-500">Klik untuk upload foto</span>
                                </div>
                                <input type="file" accept="image/*" onChange={handleAvatarChange} className="hidden" />
                            </label>
                            {fieldError('avatar')}
                        </div>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <label className="block sm:col-span-2">
                                <span>{requiredLabel('Nama lengkap')}</span>
                                <input value={data.name} onChange={(event) => setData('name', event.target.value)} className={inputClass('name')} />
                                {fieldError('name')}
                            </label>
                            <label className="block">
                                <span>{requiredLabel('Username')}</span>
                                <input
                                    value={data.username}
                                    onChange={(event) => setData('username', event.target.value)}
                                    className={inputClass('username')}
                                />
                                {fieldError('username')}
                            </label>
                            <label className="block">
                                <span>{optionalLabel('Email')}</span>
                                <input
                                    type="email"
                                    value={data.email}
                                    onChange={(event) => setData('email', event.target.value)}
                                    className={inputClass('email')}
                                />
                                {fieldError('email')}
                            </label>
                            <label className="block">
                                <span>{requiredLabel('Jenis kelamin')}</span>
                                <FormSelect
                                    value={data.jenis_kelamin}
                                    options={[
                                        { value: 'Laki-laki', label: 'Laki-laki' },
                                        { value: 'Perempuan', label: 'Perempuan' },
                                    ]}
                                    onChange={(value) => setData('jenis_kelamin', value)}
                                    placeholder="Pilih jenis kelamin"
                                    error={errors.jenis_kelamin}
                                />
                                {fieldError('jenis_kelamin')}
                            </label>
                            <label className="block">
                                <span>{requiredLabel('Role')}</span>
                                <FormSelect
                                    value={data.role}
                                    options={roles.map((role) => ({ value: role.name, label: role.name }))}
                                    onChange={(value) => setData('role', value)}
                                    placeholder="Pilih role"
                                    error={errors.role}
                                />
                                {fieldError('role')}
                            </label>
                            <label className="block">
                                <span>{requiredLabel(editing ? 'Password baru' : 'Password')}</span>
                                <span className="relative block">
                                    <input
                                        type={showPassword ? 'text' : 'password'}
                                        value={data.password}
                                        onChange={(event) => setData('password', event.target.value)}
                                        className={`${inputClass('password', 'h-11 w-full rounded-md border px-3 pr-11 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100')}`}
                                    />{' '}
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword((value) => !value)}
                                        className="absolute top-1/2 right-3 -translate-y-1/2 text-slate-400"
                                        aria-label="Tampilkan password"
                                    >
                                        {showPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                                    </button>
                                </span>
                                {fieldError('password')}
                            </label>
                            <label className="block">
                                <span>{requiredLabel('Konfirmasi password')}</span>
                                <input
                                    type={showPassword ? 'text' : 'password'}
                                    value={data.password_confirmation}
                                    onChange={(event) => setData('password_confirmation', event.target.value)}
                                    className={inputClass('password_confirmation')}
                                />
                                {fieldError('password_confirmation')}
                            </label>
                        </div>
                    </div>
                    <div className="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <Link
                            href={editing ? `/users/${user.id}` : '/users'}
                            className="rounded-md border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50"
                        >
                            Batal
                        </Link>
                        <button
                            disabled={processing}
                            className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-700 disabled:opacity-60"
                        >
                            <Save className="size-4" /> {processing ? 'Menyimpan...' : 'Simpan'}
                        </button>
                    </div>
                </form>
            </section>
        </VillageDashboardLayout>
    );
}
