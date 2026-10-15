import { Head, Link, useForm } from '@inertiajs/react';
import { KeyRound, Save } from 'lucide-react';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

export default function PasswordSettings() {
    const { data, setData, put, processing, errors, reset } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event) => {
        event.preventDefault();
        put('/settings/password', { preserveScroll: true, onSuccess: () => reset() });
    };

    const field = (name, label) => (
        <label className="block">
            <span className="mb-2 block text-sm font-medium text-slate-700">{label} <span className="text-red-600">*</span></span>
            <input type="password" value={data[name]} onChange={(event) => setData(name, event.target.value)} className={`h-11 w-full rounded-md border px-3 text-sm outline-none focus:ring-2 ${errors[name] ? 'border-red-300 bg-red-50 focus:ring-red-100' : 'border-slate-200 focus:border-cyan-500 focus:ring-cyan-100'}`} />
            {errors[name] && <p className="mt-1 text-xs text-red-600">{errors[name]}</p>}
        </label>
    );

    return (
        <VillageDashboardLayout>
            <Head title="Ganti Password" />
            <section className="mx-auto max-w-2xl space-y-5">
                <div><p className="text-sm font-medium text-cyan-600">Pengaturan Akun</p><h1 className="mt-1 text-2xl font-bold text-slate-800">Ganti Password</h1></div>
                <form onSubmit={submit} className="space-y-5 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="flex items-center gap-3 border-b border-slate-100 pb-4"><KeyRound className="size-5 text-cyan-600" /><p className="text-sm text-slate-600">Gunakan minimal delapan karakter dan simpan password secara aman.</p></div>
                    {field('current_password', 'Password saat ini')}
                    {field('password', 'Password baru')}
                    {field('password_confirmation', 'Konfirmasi password baru')}
                    <div className="flex justify-end gap-3 border-t border-slate-100 pt-5"><Link href="/settings/profile" className="rounded-md border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600">Batal</Link><button disabled={processing} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"><Save className="size-4" />{processing ? 'Menyimpan...' : 'Simpan Password'}</button></div>
                </form>
            </section>
        </VillageDashboardLayout>
    );
}
