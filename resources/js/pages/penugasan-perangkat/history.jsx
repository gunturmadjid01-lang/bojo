import MasterResourcePage from '@/components/master-resource-page';

const formatTanggal = (value) => {
    if (!value) return '-';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }).format(date);
};

export default function History({ assignments }) {
    const data = assignments?.data || [];
    return <MasterResourcePage title="Riwayat Penugasan" group="Perangkat Desa" data={data} showActions={false} searchFields={['tanggal_mulai', 'tanggal_selesai']} searchPlaceholder="Cari riwayat..." columns={[{ name: 'Nama', selector: (item) => item.perangkat_desa?.nama_lengkap || item.perangkat_desa?.user?.name || '-', sortable: true }, { name: 'Jabatan', selector: (item) => item.jabatan_desa?.nama || '-' }, { name: 'Wilayah', selector: (item) => item.dusun?.nama || item.desa?.nama_desa || '-' }, { name: 'Tanggal Mulai', selector: (item) => formatTanggal(item.tanggal_mulai) }, { name: 'Tanggal Selesai', selector: (item) => formatTanggal(item.tanggal_selesai) }, { name: 'Nomor SK', selector: (item) => item.nomor_sk_pengangkatan || '-' }, { name: 'Status', selector: (item) => item.status || '-' }]} />;
}
