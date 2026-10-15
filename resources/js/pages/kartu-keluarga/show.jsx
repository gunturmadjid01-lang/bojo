import ResourceDataTable from '@/components/resource-data-table';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, FileText, Pencil, UserPlus } from 'lucide-react';
import { canAccessHref } from '@/lib/permissions';

export default function KartuKeluargaShow({ kartuKeluarga }) {
    const { props } = usePage();
    const user = props?.auth?.user;
    const canCreateAnggota = canAccessHref(user, '/anggota-kartu-keluarga/create', 'create');
    const canEdit = canAccessHref(user, `/kartu-keluarga/${kartuKeluarga.id}/edit`, 'edit');
    return (
        <VillageDashboardLayout>
            <Head title={`Detail ${kartuKeluarga.nomor_kk}`} />
            <section className="max-w-8xl mx-auto space-y-5">
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <Link href="/kartu-keluarga" className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600">
                            <ArrowLeft className="size-5" />
                        </Link>
                        <div>
                            <p className="text-sm font-medium text-cyan-600">Kependudukan</p>
                            <h1 className="text-2xl font-bold text-slate-800">Detail Kartu Keluarga</h1>
                        </div>
                    </div>
                    <div className="flex flex-wrap justify-end gap-2">
                        {canCreateAnggota && (
                            <Link
                                href={`/anggota-kartu-keluarga/create?kartu_keluarga_id=${kartuKeluarga.id}`}
                                className="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                            >
                                <UserPlus className="size-4" /> Daftarkan Anggota
                            </Link>
                        )}
                        {canEdit && (
                            <Link
                                href={`/kartu-keluarga/${kartuKeluarga.id}/edit`}
                                className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-700"
                            >
                                <Pencil className="size-4" /> Edit
                            </Link>
                        )}
                    </div>
                </div>

                <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center gap-4 bg-gradient-to-r from-cyan-600 to-cyan-500 p-6 text-white">
                        <div className="flex size-16 items-center justify-center rounded-full bg-white/20">
                            <FileText className="size-8" />
                        </div>
                        <div>
                            <h2 className="text-xl font-bold">{kartuKeluarga.nomor_kk}</h2>
                            <p className="text-sm text-cyan-100">{kartuKeluarga.status || 'Aktif'}</p>
                        </div>
                    </div>

                    <div className="grid gap-5 p-6 md:grid-cols-2">
                        <div>
                            <p className="text-xs tracking-wide text-slate-400 uppercase">Nomor KK</p>
                            <p className="mt-1 font-medium text-slate-800">{kartuKeluarga.nomor_kk}</p>
                        </div>
                        <div>
                            <p className="text-xs tracking-wide text-slate-400 uppercase">Kepala Keluarga</p>
                            <p className="mt-1 font-medium text-slate-800">{kartuKeluarga.kepala_keluarga?.penduduk?.nama_lengkap || '-'}</p>
                        </div>
                        <div>
                            <p className="text-xs tracking-wide text-slate-400 uppercase">Desa</p>
                            <p className="mt-1 font-medium text-slate-800">{kartuKeluarga.desa?.nama_desa || '-'}</p>
                        </div>
                        <div>
                            <p className="text-xs tracking-wide text-slate-400 uppercase">Dusun</p>
                            <p className="mt-1 font-medium text-slate-800">{kartuKeluarga.dusun?.nama || '-'}</p>
                        </div>
                        <div>
                            <p className="text-xs tracking-wide text-slate-400 uppercase">RW</p>
                            <p className="mt-1 font-medium text-slate-800">{kartuKeluarga.rw?.nomor ? `RW ${kartuKeluarga.rw.nomor}` : '-'}</p>
                        </div>
                        <div>
                            <p className="text-xs tracking-wide text-slate-400 uppercase">RT</p>
                            <p className="mt-1 font-medium text-slate-800">{kartuKeluarga.rt?.nomor ? `RT ${kartuKeluarga.rt.nomor}` : '-'}</p>
                        </div>
                        <div>
                            <p className="text-xs tracking-wide text-slate-400 uppercase">Tanggal Diterbitkan</p>
                            <p className="mt-1 font-medium text-slate-800">{kartuKeluarga.tanggal_diterbitkan || '-'}</p>
                        </div>
                        <div className="md:col-span-2">
                            <p className="text-xs tracking-wide text-slate-400 uppercase">Alamat</p>
                            <p className="mt-1 font-medium text-slate-800">{kartuKeluarga.alamat || '-'}</p>
                        </div>
                        <div>
                            <p className="text-xs tracking-wide text-slate-400 uppercase">Kode Pos</p>
                            <p className="mt-1 font-medium text-slate-800">{kartuKeluarga.kode_pos || '-'}</p>
                        </div>
                        <div>
                            <p className="text-xs tracking-wide text-slate-400 uppercase">Status</p>
                            <p className="mt-1 font-medium text-slate-800">{kartuKeluarga.status || 'Aktif'}</p>
                        </div>
                    </div>
                </div>

                <div className="space-y-3">
                    <div>
                        <h2 className="text-lg font-bold text-slate-800">Anggota Keluarga</h2>
                        <p className="text-sm text-slate-500">Daftar penduduk yang pernah atau masih terdaftar pada kartu keluarga ini.</p>
                    </div>
                    <ResourceDataTable
                        data={kartuKeluarga.anggota_keluarga || []}
                        searchFields={[(item) => item.penduduk?.nama_lengkap, (item) => item.penduduk?.nik, 'hubungan_keluarga']}
                        searchPlaceholder="Cari anggota keluarga..."
                        emptyMessage="Belum ada anggota yang terdaftar pada kartu keluarga ini."
                        itemLabel={(item) => item.penduduk?.nama_lengkap || item.id}
                        detailHref={(item) => `/anggota-kartu-keluarga/${item.id}`}
                        editHref={(item) => `/anggota-kartu-keluarga/${item.id}/edit`}
                        columns={[
                            { name: 'Nama', selector: (item) => item.penduduk?.nama_lengkap || '-', sortable: true },
                            { name: 'NIK', selector: (item) => item.penduduk?.nik || '-', sortable: true },
                            { name: 'Hubungan', selector: (item) => item.hubungan_keluarga || '-', sortable: true },
                            {
                                name: 'Tanggal Masuk',
                                selector: (item) =>
                                    item.tanggal_masuk
                                        ? new Date(item.tanggal_masuk).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
                                        : '-',
                                sortable: true,
                            },
                            { name: 'Status', selector: (item) => (item.aktif ? 'Aktif' : 'Tidak Aktif'), sortable: true },
                        ]}
                    />
                </div>
            </section>
        </VillageDashboardLayout>
    );
}
