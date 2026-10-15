import FormSelect from '@/components/form-select';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { useMemo } from 'react';

export default function Form({ jabatans = [], users = [], desas = [], dusuns = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        user_id: '',
        jabatan_desa_id: '',
        desa_id: desas[0]?.id || '',
        dusun_id: '',
        tanggal_mulai: '',
        tanggal_selesai: '',
        nomor_sk_pengangkatan: '',
        tanggal_sk_pengangkatan: '',
        catatan: '',
    });
    const jabatan = useMemo(() => jabatans.find((item) => String(item.id) === String(data.jabatan_desa_id)), [data.jabatan_desa_id, jabatans]);
    const submit = (event) => {
        event.preventDefault();
        post('/penugasan-perangkat');
    };
    const field = (name, label, type = 'text') => (
        <label className="block">
            <span className="mb-2 block text-sm font-medium text-slate-700">
                {label}
                {['user_id', 'jabatan_desa_id', 'tanggal_mulai'].includes(name) && <b className="text-red-600"> *</b>}
            </span>
            <input
                type={type}
                value={data[name]}
                onChange={(event) => setData(name, event.target.value)}
                className="h-11 w-full rounded-md border border-slate-200 px-3 text-sm"
            />
            {errors[name] && <small className="text-red-600">{errors[name]}</small>}
        </label>
    );
    return (
        <VillageDashboardLayout>
            <Head title="Tambah Penugasan" />
            <section className="mx-auto max-w-3xl space-y-5">
                <h1 className="text-2xl font-bold text-slate-800">Tambah Penugasan</h1>
                <form onSubmit={submit} className="grid gap-5 rounded-lg border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-2">
                    <label className="block">
                        <span className="mb-2 block text-sm font-medium text-slate-700">Jabatan *</span>
                        <FormSelect
                            value={data.jabatan_desa_id}
                            options={jabatans.map((item) => ({ value: String(item.id), label: `${item.nama} (${item.tingkat})` }))}
                            onChange={(value) => setData('jabatan_desa_id', value)}
                            placeholder="Pilih jabatan"
                            error={errors.jabatan_desa_id}
                        />
                    </label>
                    <label className="block">
                        <span className="mb-2 block text-sm font-medium text-slate-700">Pengguna *</span>
                        <FormSelect
                            value={data.user_id}
                            options={users.map((item) => ({ value: String(item.id), label: `${item.name} (${item.username})` }))}
                            onChange={(value) => setData('user_id', value)}
                            placeholder="Pilih pengguna"
                            error={errors.user_id}
                        />
                    </label>
                    {jabatan?.tingkat === 'dusun' && (
                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Dusun *</span>
                            <FormSelect
                                value={data.dusun_id}
                                options={dusuns
                                    .filter((item) => String(item.desa_id) === String(data.desa_id))
                                    .map((item) => ({ value: String(item.id), label: item.nama }))}
                                onChange={(value) => setData('dusun_id', value)}
                                placeholder="Pilih dusun"
                                error={errors.dusun_id}
                            />
                        </label>
                    )}
                    {field('tanggal_mulai', 'Tanggal Mulai', 'date')}
                    {field('tanggal_selesai', 'Tanggal Selesai', 'date')}
                    {field('nomor_sk_pengangkatan', 'Nomor SK Pengangkatan')}
                    {field('tanggal_sk_pengangkatan', 'Tanggal SK Pengangkatan', 'date')}
                    <label className="block sm:col-span-2">
                        <span className="mb-2 block text-sm font-medium text-slate-700">Catatan</span>
                        <textarea
                            value={data.catatan}
                            onChange={(event) => setData('catatan', event.target.value)}
                            className="w-full rounded-md border border-slate-200 p-3 text-sm"
                            rows="3"
                        />
                    </label>
                    <div className="flex justify-end gap-3 sm:col-span-2">
                        <Link href="/penugasan-perangkat" className="rounded-md border border-slate-200 px-4 py-2.5 text-sm">
                            Batal
                        </Link>
                        <button
                            disabled={processing}
                            className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white"
                        >
                            <Save className="size-4" />
                            Simpan
                        </button>
                    </div>
                </form>
            </section>
        </VillageDashboardLayout>
    );
}
