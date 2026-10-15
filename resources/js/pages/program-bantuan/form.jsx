import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import FormSelect from '@/components/form-select';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';
import InputUang from '@/components/input-uang';

export default function ProgramBantuanForm({ programBantuan }) {
    const editing = Boolean(programBantuan);
    const { data, setData, post, put, processing, errors } = useForm({
        nama: programBantuan?.nama || '',
        sumber: programBantuan?.sumber || '',
        tahun: programBantuan?.tahun || new Date().getFullYear(),
        tanggal_mulai: programBantuan?.tanggal_mulai || '',
        tanggal_selesai: programBantuan?.tanggal_selesai || '',
        anggaran: programBantuan?.anggaran || '',
        status: programBantuan?.status || 'aktif',
        keterangan: programBantuan?.keterangan || '',
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
        editing ? put(`/program-bantuan/${programBantuan.id}`) : post('/program-bantuan');
    };

    const fieldError = (field) => errors[field] && <p className="mt-1 text-xs text-red-600">{errors[field]}</p>;

    return (
        <VillageDashboardLayout>
            <Head title={editing ? 'Edit Program Bantuan' : 'Tambah Program Bantuan'} />
            <section className="mx-auto max-w-8xl space-y-5">
                <div className="flex items-center gap-3">
                    <Link href={editing ? `/program-bantuan/${programBantuan.id}` : '/program-bantuan'} className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link>
                    <div>
                        <p className="text-sm font-medium text-cyan-600">Master Data</p>
                        <h1 className="text-2xl font-bold text-slate-800">{editing ? 'Edit Program Bantuan' : 'Tambah Program Bantuan'}</h1>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <div className="grid gap-5 sm:grid-cols-2">
                        <label className="block sm:col-span-2">
                            <span>{requiredLabel('Nama Program')}</span>
                            <input value={data.nama} onChange={(event) => setData('nama', event.target.value)} className={inputClass('nama')} />
                            {fieldError('nama')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Sumber</span>
                            <input value={data.sumber} onChange={(event) => setData('sumber', event.target.value)} className={inputClass('sumber')} />
                            {fieldError('sumber')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Tahun</span>
                            <input type="number" value={data.tahun} onChange={(event) => setData('tahun', event.target.value)} className={inputClass('tahun')} />
                            {fieldError('tahun')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Tanggal Mulai</span>
                            <input type="date" value={data.tanggal_mulai} onChange={(event) => setData('tanggal_mulai', event.target.value)} className={inputClass('tanggal_mulai')} />
                            {fieldError('tanggal_mulai')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Tanggal Selesai</span>
                            <input type="date" value={data.tanggal_selesai} onChange={(event) => setData('tanggal_selesai', event.target.value)} className={inputClass('tanggal_selesai')} />
                            {fieldError('tanggal_selesai')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Anggaran</span>
                            <InputUang value={data.anggaran} onChange={(value) => setData('anggaran', value)} className={inputClass('anggaran')} placeholder="Rp 0" />
                            {fieldError('anggaran')}
                        </label>

                        <label className="block">
                            <span>{requiredLabel('Status')}</span>
                            <FormSelect value={data.status} options={[{ value: 'aktif', label: 'Aktif' }, { value: 'nonaktif', label: 'Nonaktif' }, { value: 'selesai', label: 'Selesai' }, { value: 'draft', label: 'Draft' }]} onChange={(value) => setData('status', value)} placeholder="Pilih status" error={errors.status} />
                            {fieldError('status')}
                        </label>

                        <label className="block sm:col-span-2">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Keterangan</span>
                            <textarea value={data.keterangan} onChange={(event) => setData('keterangan', event.target.value)} rows="3" className={`${inputClass('keterangan', 'w-full rounded-md border px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100')} ${errors.keterangan ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-100' : 'border-slate-200 bg-white'}`} />
                            {fieldError('keterangan')}
                        </label>
                    </div>

                    <div className="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <Link href={editing ? `/program-bantuan/${programBantuan.id}` : '/program-bantuan'} className="rounded-md border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</Link>
                        <button disabled={processing} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-700 disabled:opacity-60"><Save className="size-4" /> {processing ? 'Menyimpan...' : 'Simpan'}</button>
                    </div>
                </form>
            </section>
        </VillageDashboardLayout>
    );
}
