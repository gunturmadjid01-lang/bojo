import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import FormSelect from '@/components/form-select';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

const defaultForm = {
    penduduk_id: '',
    jenis_disabilitas_id: '',
    tingkat: '',
    keterangan: '',
};

export default function DisabilitasPendudukForm({ disabilitasPenduduk, penduduks = [], disabilitas = [] }) {
    const editing = Boolean(disabilitasPenduduk);
    const { data, setData, post, put, processing, errors } = useForm({
        ...defaultForm,
        ...(disabilitasPenduduk || {}),
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

    const fieldError = (field) => errors[field] && <p className="mt-1 text-xs text-red-600">{errors[field]}</p>;

    const submit = (event) => {
        event.preventDefault();
        editing ? put(`/disabilitas-penduduk/${disabilitasPenduduk.id}`) : post('/disabilitas-penduduk');
    };

    return (
        <VillageDashboardLayout>
            <Head title={editing ? 'Edit Data Disabilitas' : 'Tambah Data Disabilitas'} />
            <section className="mx-auto max-w-8xl space-y-5">
                <div className="flex items-center gap-3">
                    <Link href={editing ? `/disabilitas-penduduk/${disabilitasPenduduk.id}` : '/disabilitas-penduduk'} className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link>
                    <div>
                        <p className="text-sm font-medium text-cyan-600">Data Sosial</p>
                        <h1 className="text-2xl font-bold text-slate-800">{editing ? 'Edit Data Disabilitas' : 'Tambah Data Disabilitas'}</h1>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <div className="grid gap-5 md:grid-cols-2">
                        <label className="block">
                            <span>{requiredLabel('Penduduk')}</span>
                            <FormSelect value={data.penduduk_id || ''} options={penduduks.map((item) => ({ value: String(item.id), label: `${item.nama_lengkap} (${item.nik})` }))} onChange={(value) => setData('penduduk_id', value)} placeholder="Pilih penduduk" error={errors.penduduk_id} className="w-full" />
                            {fieldError('penduduk_id')}
                        </label>

                        <label className="block">
                            <span>{requiredLabel('Jenis Disabilitas')}</span>
                            <FormSelect value={data.jenis_disabilitas_id || ''} options={disabilitas.map((item) => ({ value: String(item.id), label: item.nama }))} onChange={(value) => setData('jenis_disabilitas_id', value)} placeholder="Pilih disabilitas" error={errors.jenis_disabilitas_id} className="w-full" />
                            {fieldError('jenis_disabilitas_id')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Tingkat</span>
                            <input value={data.tingkat || ''} onChange={(event) => setData('tingkat', event.target.value)} className={inputClass('tingkat')} />
                            {fieldError('tingkat')}
                        </label>

                        <label className="block md:col-span-2">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Keterangan</span>
                            <textarea value={data.keterangan || ''} onChange={(event) => setData('keterangan', event.target.value)} rows={3} className={inputClass('keterangan', 'w-full rounded-md border px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100')}></textarea>
                            {fieldError('keterangan')}
                        </label>
                    </div>

                    <div className="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <Link href={editing ? `/disabilitas-penduduk/${disabilitasPenduduk.id}` : '/disabilitas-penduduk'} className="rounded-md border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</Link>
                        <button disabled={processing} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-700 disabled:opacity-60"><Save className="size-4" /> {processing ? 'Menyimpan...' : 'Simpan'}</button>
                    </div>
                </form>
            </section>
        </VillageDashboardLayout>
    );
}
