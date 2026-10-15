import ResourceDataTable from './resource-data-table';

const defaultColumns = [
    { name: 'NIK', selector: (row) => row.nik || '-', sortable: true },
    { name: 'Nama', selector: (row) => row.nama || '-', sortable: true },
    { name: 'Tanggal Lahir', selector: (row) => row.tanggalLahir || '-', sortable: true },
    { name: 'Jenis Kelamin', selector: (row) => row.jenisKelamin || '-', sortable: true },
    { name: 'Pendidikan', selector: (row) => row.pendidikan || '-', sortable: true },
    { name: 'Dusun', selector: (row) => row.dusun || '-', sortable: true },
    { name: 'Status Tinggal', selector: (row) => row.statusTinggal || '-', sortable: true },
];

export default function DataTable({ data = [], columns = defaultColumns, title = 'DATA PENDUDUK' }) {
    const tableColumns = columns.map((column) => column.selector ? column : { name: column.label, selector: (row) => row[column.key] ?? '-', sortable: column.sortable });
    return <section className="overflow-hidden rounded-sm border border-slate-200 bg-white shadow-sm"><h1 className="border-b border-slate-100 px-4 py-4 text-center text-xl font-bold text-slate-800">{title}</h1><ResourceDataTable data={data} columns={tableColumns} searchFields={['nik', 'nama', 'tanggalLahir', 'jenisKelamin', 'pendidikan', 'dusun', 'statusTinggal']} showActions={false} /></section>;
}
