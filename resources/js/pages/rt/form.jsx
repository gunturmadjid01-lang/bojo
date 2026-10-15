import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, Save, Trash2 } from 'lucide-react';
import FormSelect from '@/components/form-select';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

export default function RTForm({ rt, rws = [] }) {
    const editing = Boolean(rt);
    const defaultEntry = () => ({
        rw_id: rws[0]?.id || '',
        nomor: '',
        aktif: true,
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

    const { data, setData, post, put, processing, errors } = useForm({
        entries: editing ? [{ rw_id: rt.rw_id || rws[0]?.id || '', nomor: rt.nomor || '', aktif: rt.aktif ?? true }] : [defaultEntry()],
    });

    const updateEntry = (index, key, value) => {
        const next = [...data.entries];
        next[index] = { ...next[index], [key]: value };
        setData('entries', next);
    };

    const addEntry = () => {
        setData('entries', [...data.entries, defaultEntry()]);
    };

    const removeEntry = (index) => {
        if (data.entries.length === 1) {
            setData('entries', [defaultEntry()]);
            return;
        }

        setData('entries', data.entries.filter((_, itemIndex) => itemIndex !== index));
    };

    const submit = (event) => {
        event.preventDefault();
        editing ? put(`/rt/${rt.id}`) : post('/rt');
    };

    const fieldError = (path) => errors[path] && <p className="mt-1 text-xs text-red-600">{errors[path]}</p>;

    return (
        <VillageDashboardLayout>
            <Head title={editing ? 'Edit RT' : 'Tambah RT'} />
            <section className="mx-auto max-w-8xl space-y-5">
                <div className="flex items-center gap-3">
                    <Link href={editing ? `/rt/${rt.id}` : '/rt'} className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link>
                    <div>
                        <p className="text-sm font-medium text-cyan-600">Master Wilayah</p>
                        <h1 className="text-2xl font-bold text-slate-800">{editing ? 'Edit RT' : 'Tambah RT'} Multi Insert</h1>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <div className="flex items-center justify-between gap-3 border-b border-slate-100 pb-4">
                        <p className="text-sm text-slate-600">Isi beberapa RT dalam satu kali simpan.</p>
                        <button type="button" onClick={addEntry} className="inline-flex items-center gap-2 rounded-md border border-cyan-200 bg-cyan-50 px-3 py-2 text-sm font-medium text-cyan-700 hover:bg-cyan-100"><Plus className="size-4" /> Tambah baris</button>
                    </div>

                    <div className="space-y-4">
                        {data.entries.map((entry, index) => (
                            <div key={`entry-${index}`} className="grid gap-4 rounded-lg border border-slate-200 p-4 sm:grid-cols-2">
                                <label className="block">
                                    <span>{requiredLabel('RW')}</span>
                                    <FormSelect value={entry.rw_id} options={rws.map((item) => ({ value: String(item.id), label: `RW ${item.nomor}` }))} onChange={(value) => updateEntry(index, 'rw_id', value)} placeholder="Pilih RW" error={errors[`entries.${index}.rw_id`]} className="w-full" />
                                    {fieldError(`entries.${index}.rw_id`)}
                                </label>
                                <label className="block">
                                    <span>{requiredLabel('Nomor RT')}</span>
                                    <div className="flex gap-2">
                                        <input value={entry.nomor} onChange={(event) => updateEntry(index, 'nomor', event.target.value)} className={inputClass(`entries.${index}.nomor`, 'h-11 w-full rounded-md border px-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100')} />
                                        {data.entries.length > 1 && (
                                            <button type="button" onClick={() => removeEntry(index)} className="inline-flex h-11 items-center justify-center rounded-md border border-red-200 bg-red-50 p-2 text-red-600 hover:bg-red-100" aria-label="Hapus baris">
                                                <Trash2 className="size-4" />
                                            </button>
                                        )}
                                    </div>
                                    {fieldError(`entries.${index}.nomor`)}
                                </label>
                                <label className="flex items-center gap-3 rounded-md border border-slate-200 p-3 sm:col-span-2">
                                    <input type="checkbox" checked={entry.aktif} onChange={(event) => updateEntry(index, 'aktif', event.target.checked)} className="size-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500" />
                                    <span className="text-sm font-medium text-slate-700">Aktif</span>
                                </label>
                            </div>
                        ))}
                    </div>

                    {fieldError('entries')}

                    <div className="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <Link href={editing ? `/rt/${rt.id}` : '/rt'} className="rounded-md border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</Link>
                        <button disabled={processing} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-700 disabled:opacity-60"><Save className="size-4" /> {processing ? 'Menyimpan...' : 'Simpan semua'}</button>
                    </div>
                </form>
            </section>
        </VillageDashboardLayout>
    );
}
