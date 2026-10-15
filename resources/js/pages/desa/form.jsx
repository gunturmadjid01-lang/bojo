import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ImagePlus, Save } from 'lucide-react';
import { useState } from 'react';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

export default function DesaForm({ desa }) {
    const editing = Boolean(desa);
    const [preview, setPreview] = useState(desa?.logo ? `/storage/${desa.logo}` : '');
    const { data, setData, post, put, processing, errors } = useForm({
        _method:'put',
        kode_desa: desa?.kode_desa || '',
        nama_desa: desa?.nama_desa || '',
        kecamatan: desa?.kecamatan || '',
        kabupaten: desa?.kabupaten || '',
        provinsi: desa?.provinsi || '',
        kode_pos: desa?.kode_pos || '',
        alamat_kantor: desa?.alamat_kantor || '',
        telepon: desa?.telepon || '',
        email: desa?.email || '',
        logo: null,
        aktif: desa?.aktif ?? true,
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

    const optionalLabel = (label) => (
        <span className="mb-2 block text-sm font-medium text-slate-700">{label}</span>
    );

    const handleLogoChange = (event) => {
        const file = event.target.files?.[0];
        if (!file) return;

        setData('logo', file);
        setPreview(URL.createObjectURL(file));
    };

    const submit = (event) => {
        event.preventDefault();

        const hasNewLogo = Boolean(data.logo instanceof File || data.logo instanceof Blob);

        if (editing && !hasNewLogo) {
            put(`/desa/${desa.id}`, {
                _method: 'put',
                kode_desa: data.kode_desa,
                nama_desa: data.nama_desa,
                kecamatan: data.kecamatan,
                kabupaten: data.kabupaten,
                provinsi: data.provinsi,
                kode_pos: data.kode_pos,
                alamat_kantor: data.alamat_kantor,
                telepon: data.telepon,
                email: data.email,
                aktif: data.aktif,
            });
            return;
        }

        const formData = new FormData();
        Object.entries(data).forEach(([key, value]) => {
            if (key === 'logo' && value) {
                formData.append('logo', value);
                return;
            }

            if (value !== null && value !== undefined) {
                formData.append(key, value);
            }
        });

        if (editing) {
            post(`/desa/${desa.id}`, { data: formData, forceFormData: true });
            return;
        }

        post('/desa', { data: formData, forceFormData: true });
    };

    const fieldError = (field) => errors[field] && <p className="mt-1 text-xs text-red-600">{errors[field]}</p>;

    return (
        <VillageDashboardLayout>
            <Head title={editing ? 'Edit Desa' : 'Tambah Desa'} />
            <section className="mx-auto max-w-8xl space-y-5">
                <div className="flex items-center gap-3">
                    <Link href={editing ? `/desa/${desa.id}` : '/desa'} className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link>
                    <div>
                        <p className="text-sm font-medium text-cyan-600">Pengaturan</p>
                        <h1 className="text-2xl font-bold text-slate-800">{editing ? 'Edit Desa' : 'Setting Desa'}</h1>
                    </div>
                </div>

                <form onSubmit={submit} encType="multipart/form-data" className="space-y-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <div className="grid gap-5 lg:grid-cols-[220px_1fr]">
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <label className="block cursor-pointer">
                                <span className="mb-3 block text-sm font-medium text-slate-700">Logo Desa</span>
                                <div className="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-slate-300 bg-white p-4">
                                    {preview ? (
                                        <img src={preview} alt="Preview logo" className="size-24 rounded-full border border-slate-200 object-cover" />
                                    ) : (
                                        <div className="flex size-24 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                            <ImagePlus className="size-8" />
                                        </div>
                                    )}
                                    <span className="text-center text-xs text-slate-500">Klik untuk upload logo</span>
                                </div>
                                <input type="file" accept="image/*" onChange={handleLogoChange} className="hidden" />
                            </label>
                            {fieldError('logo')}
                        </div>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <label className="block"><span>{optionalLabel('Kode Desa')}</span><input value={data.kode_desa} onChange={(event) => setData('kode_desa', event.target.value)} className={inputClass('kode_desa')} />{fieldError('kode_desa')}</label>
                            <label className="block"><span>{requiredLabel('Nama Desa')}</span><input value={data.nama_desa} onChange={(event) => setData('nama_desa', event.target.value)} className={inputClass('nama_desa')} />{fieldError('nama_desa')}</label>
                            <label className="block"><span>{optionalLabel('Kecamatan')}</span><input value={data.kecamatan} onChange={(event) => setData('kecamatan', event.target.value)} className={inputClass('kecamatan')} />{fieldError('kecamatan')}</label>
                            <label className="block"><span>{optionalLabel('Kabupaten')}</span><input value={data.kabupaten} onChange={(event) => setData('kabupaten', event.target.value)} className={inputClass('kabupaten')} />{fieldError('kabupaten')}</label>
                            <label className="block"><span>{optionalLabel('Provinsi')}</span><input value={data.provinsi} onChange={(event) => setData('provinsi', event.target.value)} className={inputClass('provinsi')} />{fieldError('provinsi')}</label>
                            <label className="block"><span>{optionalLabel('Kode Pos')}</span><input value={data.kode_pos} onChange={(event) => setData('kode_pos', event.target.value)} className={inputClass('kode_pos')} />{fieldError('kode_pos')}</label>
                            <label className="block sm:col-span-2"><span>{optionalLabel('Alamat Kantor')}</span><textarea value={data.alamat_kantor} onChange={(event) => setData('alamat_kantor', event.target.value)} rows="3" className={`${inputClass('alamat_kantor', 'w-full rounded-md border px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100')} ${errors.alamat_kantor ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-100' : 'border-slate-200 bg-white'}`} />{fieldError('alamat_kantor')}</label>
                            <label className="block"><span>{optionalLabel('Telepon')}</span><input value={data.telepon} onChange={(event) => setData('telepon', event.target.value)} className={inputClass('telepon')} />{fieldError('telepon')}</label>
                            <label className="block"><span>{optionalLabel('Email')}</span><input type="email" value={data.email} onChange={(event) => setData('email', event.target.value)} className={inputClass('email')} />{fieldError('email')}</label>
                            <label className="flex items-center gap-3 rounded-md border border-slate-200 p-3 sm:col-span-2">
                                <input type="checkbox" checked={data.aktif} onChange={(event) => setData('aktif', event.target.checked)} className="size-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500" />
                                <span className="text-sm font-medium text-slate-700">Aktif</span>
                            </label>
                        </div>
                    </div>

                    <div className="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <Link href={editing ? `/desa/${desa.id}` : '/desa'} className="rounded-md border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</Link>
                        <button disabled={processing} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-700 disabled:opacity-60"><Save className="size-4" /> {processing ? 'Menyimpan...' : 'Simpan'}</button>
                    </div>
                </form>
            </section>
        </VillageDashboardLayout>
    );
}
