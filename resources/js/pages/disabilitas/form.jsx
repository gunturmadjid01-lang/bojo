import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

export default function DisabilitasForm({ disabilitas }) {
    const editing = Boolean(disabilitas);
    const { data, setData, post, put, processing, errors } = useForm({
        nama: disabilitas?.nama || '',
        keterangan: disabilitas?.keterangan || '',
        aktif: disabilitas?.aktif ?? true,
    });

    const inputClass = (field, baseClass = 'h-11 w-full rounded-md border px-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100') => {
        const errorClass = errors[field] ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-100' : 'border-slate-200 bg-white';
        return `${baseClass} ${errorClass}`;
    };

    const requiredLabel = (label) => (
        <span className="mb-2 flex items-center gap-1 text-sm font-medium text-slate-700">
            <span>{label}</span>
            <span className="text-red-600">*</span>
        </span>
    );

    const submit = (event) => {
        event.preventDefault();
        editing ? put(`/disabilitas/${disabilitas.id}`) : post('/disabilitas');
    };

    const fieldError = (field) => errors[field] && <p className="mt-1 text-xs text-red-600">{errors[field]}</p>;

    return (
        <VillageDashboardLayout>
            <Head title={editing ? 'Edit Jenis Disabilitas' : 'Tambah Jenis Disabilitas'} />
            <section className="mx-auto max-w-8xl space-y-5">
                <div className="flex items-center gap-3">
                    <Link href={editing ? `/disabilitas/${disabilitas.id}` : '/disabilitas'} className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link>
                    <div>
                        <p className="text-sm font-medium text-cyan-600">Master Data</p>
                        <h1 className="text-2xl font-bold text-slate-800">{editing ? 'Edit Jenis Disabilitas' : 'Tambah Jenis Disabilitas'}</h1>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <div className="grid gap-5 sm:grid-cols-2">
                        <label className="block sm:col-span-2">
                            <span>{requiredLabel('Nama Disabilitas')}</span>
                            <input value={data.nama} onChange={(event) => setData('nama', event.target.value)} className={inputClass('nama')} />
                            {fieldError('nama')}
                        </label>

                        <label className="block sm:col-span-2">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Keterangan</span>
                            <textarea value={data.keterangan} onChange={(event) => setData('keterangan', event.target.value)} rows="3" className={`${inputClass('keterangan', 'w-full rounded-md border px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100')} ${errors.keterangan ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-100' : 'border-slate-200 bg-white'}`} />
                            {fieldError('keterangan')}
                        </label>

                        <label className="flex items-center gap-3 rounded-md border border-slate-200 p-3 sm:col-span-2">
                            <input type="checkbox" checked={data.aktif} onChange={(event) => setData('aktif', event.target.checked)} className="size-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500" />
                            <span className="text-sm font-medium text-slate-700">Aktif</span>
                        </label>
                    </div>

                    <div className="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <Link href={editing ? `/disabilitas/${disabilitas.id}` : '/disabilitas'} className="rounded-md border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</Link>
                        <button disabled={processing} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-700 disabled:opacity-60"><Save className="size-4" /> {processing ? 'Menyimpan...' : 'Simpan'}</button>
                    </div>
                </form>
            </section>
        </VillageDashboardLayout>
    );
}
