import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, Save, Trash2 } from 'lucide-react';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

const emptyRt = () => ({ nomor: '', aktif: true });
const emptyRw = () => ({ nomor: '', aktif: true, rts: [emptyRt()] });

export default function DusunForm({ dusun, desas = [] }) {
    const editing = Boolean(dusun);
    const { data, setData, post, put, processing, errors } = useForm({
        desa_id: dusun?.desa_id || desas[0]?.id || '',
        nama: dusun?.nama || '',
        aktif: dusun?.aktif ?? true,
        rws: dusun?.rws?.map((rw) => ({ id: rw.id, nomor: rw.nomor, aktif: rw.aktif, rts: rw.rts?.map((rt) => ({ id: rt.id, nomor: rt.nomor, aktif: rt.aktif })) || [] })) || [emptyRw()],
    });
    const inputClass = (field) => `h-11 w-full rounded-md border px-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100 ${errors[field] ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-white'}`;
    const updateRw = (index, key, value) => setData('rws', data.rws.map((row, rowIndex) => rowIndex === index ? { ...row, [key]: value } : row));
    const updateRt = (rwIndex, rtIndex, key, value) => updateRw(rwIndex, 'rts', data.rws[rwIndex].rts.map((row, rowIndex) => rowIndex === rtIndex ? { ...row, [key]: value } : row));
    const removeRw = (index) => setData('rws', data.rws.filter((_, rowIndex) => rowIndex !== index));
    const removeRt = (rwIndex, rtIndex) => updateRw(rwIndex, 'rts', data.rws[rwIndex].rts.filter((_, rowIndex) => rowIndex !== rtIndex));
    const submit = (event) => { event.preventDefault(); editing ? put(`/dusun/${dusun.id}`) : post('/dusun'); };
    const error = (field) => errors[field] && <p className="mt-1 text-xs text-red-600">{errors[field]}</p>;
    const fieldLabel = (field) => {
        if (field === 'nama') return 'Nama Dusun';
        if (field === 'desa_id') return 'Desa';
        const rt = field.match(/^rws\.(\d+)\.rts\.(\d+)\.nomor$/);
        if (rt) return `Nomor RT ${Number(rt[2]) + 1} pada RW ${Number(rt[1]) + 1}`;
        const rw = field.match(/^rws\.(\d+)\.nomor$/);
        if (rw) return `Nomor RW ${Number(rw[1]) + 1}`;
        return field;
    };
    const validationSummary = Object.entries(errors).flatMap(([field, messages]) => (Array.isArray(messages) ? messages : [messages]).filter(Boolean).map((message) => ({ field, message })));

    return <VillageDashboardLayout>
        <Head title={editing ? 'Edit Dusun' : 'Tambah Dusun'} />
        <section className="mx-auto max-w-5xl space-y-5">
            <div className="flex items-center gap-3"><Link href={editing ? `/dusun/${dusun.id}` : '/dusun'} className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link><div><p className="text-sm font-medium text-cyan-600">Master Wilayah</p><h1 className="text-2xl font-bold text-slate-800">{editing ? 'Edit Dusun, RW & RT' : 'Tambah Dusun, RW & RT'}</h1></div></div>
            <form onSubmit={submit} className="space-y-6">
                {validationSummary.length > 0 && <div role="alert" className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"><p className="font-semibold">Periksa field berikut:</p><ul className="mt-2 list-disc space-y-1 pl-5">{validationSummary.map(({ field, message }) => <li key={`${field}-${message}`}><span className="font-medium">{fieldLabel(field)}:</span> {message}</li>)}</ul></div>}
                <div className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <label className="block"><span className="mb-2 block text-sm font-medium text-slate-700">Nama Dusun <span className="text-red-600">*</span></span><input value={data.nama} onChange={(event) => setData('nama', event.target.value)} className={inputClass('nama')} />{error('nama')}</label>
                    <label className="mt-4 flex items-center gap-3"><input type="checkbox" checked={data.aktif} onChange={(event) => setData('aktif', event.target.checked)} className="size-4" /><span className="text-sm font-medium text-slate-700">Dusun aktif</span></label>
                </div>
                <div className="space-y-4">
                    <div className="flex items-center justify-between"><div><h2 className="text-lg font-bold text-slate-800">Daftar RW dan RT</h2><p className="text-sm text-slate-500">Tambahkan beberapa RW, lalu masukkan RT di bawah RW terkait.</p></div><button type="button" onClick={() => setData('rws', [...data.rws, emptyRw()])} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-3 py-2 text-sm font-semibold text-white"><Plus className="size-4" /> Tambah RW</button></div>
                    {error('rws')}
                    {data.rws.map((rw, rwIndex) => <div key={rw.id || `rw-${rwIndex}`} className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="flex items-start gap-3"><label className="flex-1"><span className="mb-2 block text-sm font-medium text-slate-700">Nomor RW <span className="text-red-600">*</span></span><input value={rw.nomor} onChange={(event) => updateRw(rwIndex, 'nomor', event.target.value)} placeholder="Contoh: 001" className={inputClass(`rws.${rwIndex}.nomor`)} />{error(`rws.${rwIndex}.nomor`)}</label>{!rw.id && <button type="button" onClick={() => removeRw(rwIndex)} className="mt-7 rounded-md p-2 text-red-600 hover:bg-red-50" aria-label="Hapus RW"><Trash2 className="size-5" /></button>}</div>
                        <label className="mt-3 flex items-center gap-2"><input type="checkbox" checked={rw.aktif} onChange={(event) => updateRw(rwIndex, 'aktif', event.target.checked)} /><span className="text-sm text-slate-600">RW aktif</span></label>
                        <div className="mt-5 border-t border-slate-100 pt-4"><div className="mb-3 flex items-center justify-between"><h3 className="font-semibold text-slate-700">RT di RW {rw.nomor || rwIndex + 1}</h3><button type="button" onClick={() => updateRw(rwIndex, 'rts', [...rw.rts, emptyRt()])} className="inline-flex items-center gap-1 rounded-md border border-cyan-200 px-3 py-1.5 text-sm font-medium text-cyan-700"><Plus className="size-4" /> Tambah RT</button></div>{error(`rws.${rwIndex}.rts`)}
                            <div className="grid gap-3 sm:grid-cols-2">{rw.rts.map((rt, rtIndex) => <div key={rt.id || `rt-${rtIndex}`} className="rounded-md border border-slate-200 p-3"><div className="flex gap-2"><input value={rt.nomor} onChange={(event) => updateRt(rwIndex, rtIndex, 'nomor', event.target.value)} placeholder="Nomor RT, contoh: 001" className={inputClass(`rws.${rwIndex}.rts.${rtIndex}.nomor`)} />{!rt.id && <button type="button" onClick={() => removeRt(rwIndex, rtIndex)} className="text-red-600" aria-label="Hapus RT"><Trash2 className="size-4" /></button>}</div>{error(`rws.${rwIndex}.rts.${rtIndex}.nomor`)}<label className="mt-2 flex items-center gap-2"><input type="checkbox" checked={rt.aktif} onChange={(event) => updateRt(rwIndex, rtIndex, 'aktif', event.target.checked)} /><span className="text-xs text-slate-600">RT aktif</span></label></div>)}</div>
                        </div>
                    </div>)}
                </div>
                <div className="flex justify-end gap-3"><Link href={editing ? `/dusun/${dusun.id}` : '/dusun'} className="rounded-md border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600">Batal</Link><button disabled={processing} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"><Save className="size-4" /> {processing ? 'Menyimpan...' : 'Simpan Dusun, RW & RT'}</button></div>
            </form>
        </section>
    </VillageDashboardLayout>;
}
