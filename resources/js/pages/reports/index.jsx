import { Head, Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

const sections = [
    ['penduduk', 'Penduduk'], ['kartu-keluarga', 'Keluarga'],
];
const matrixLabels = { gender: 'Jenis Kelamin', age: 'Kelompok Umur', education: 'Pendidikan', occupation: 'Pekerjaan' };

export default function ReportsIndex({ moduleStatistics = {}, statisticYear, statisticYears = [] }) {
    const [active, setActive] = useState('penduduk');
    const current = moduleStatistics[active]?.statistics || {};
    const matrix = current.demographicsByRegion || {};
    const updateYear = (year) => router.get('/laporan', { statistik_tahun: year }, { preserveState: true, replace: true });
    return <VillageDashboardLayout><Head title="Laporan & Statistik" /><section className="mx-auto max-w-8xl space-y-5">
        <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-end"><div><p className="text-sm font-medium text-cyan-600">Kepala Desa & Sekretaris Desa</p><h1 className="text-2xl font-bold text-slate-800">Laporan & Statistik</h1></div><select value={statisticYear} onChange={(event) => updateYear(event.target.value)} className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm">{statisticYears.map((year) => <option key={year} value={year}>{year}</option>)}</select></div>
        <nav className="flex gap-2 overflow-x-auto border-b border-slate-200 pb-1">{sections.map(([key, label]) => <button key={key} type="button" onClick={() => setActive(key)} className={`whitespace-nowrap rounded-t-md px-4 py-2 text-sm font-semibold ${active === key ? 'bg-cyan-600 text-white' : 'bg-white text-slate-500'}`}>{label}</button>)}</nav>
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">{(current.cards || []).map((card) => <div key={card.label} className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"><p className="text-xs font-medium text-slate-500">{card.label}</p><p className="mt-2 text-2xl font-bold text-slate-800">{card.value}</p></div>)}</div>
        {active === 'penduduk' && Object.entries(matrixLabels).map(([key, label]) => <Matrix title={`${label} per Dusun`} data={transpose(matrix[key] || {})} key={key} />)}
        {active !== 'penduduk' && <><Matrix title={current.regionTitle || 'Jumlah Data per Dusun'} data={{ Total: Object.fromEntries((current.regionValues || []).map((row) => [row.label, row.value])) }} /><div className="grid gap-5 lg:grid-cols-2">{Object.entries(current.demographics || {}).map(([key, rows]) => <Matrix key={key} title={key.replaceAll('_', ' ')} data={{ Total: Object.fromEntries((rows || []).map((row) => [row.label, row.value])) }} />)}</div></>}
        <Link href="/dashboard" className="inline-flex rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600">Kembali ke Dashboard</Link>
    </section></VillageDashboardLayout>;
}

function Matrix({ title, data }) {
    const regions = useMemo(() => [...new Set(Object.values(data).flatMap((values) => Object.keys(values || {})))], [data]);
    const labels = Object.keys(data);
    return <section className="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm"><h2 className="border-b border-slate-100 px-4 py-3 text-base font-semibold capitalize text-slate-800">{title}</h2><table className="w-full min-w-[560px] text-left text-sm"><thead><tr className="bg-slate-50 text-xs uppercase text-slate-500"><th className="px-4 py-3">Kategori</th>{regions.map((region) => <th key={region} className="px-4 py-3 text-right">{region}</th>)}<th className="px-4 py-3 text-right">Total</th></tr></thead><tbody>{labels.map((label) => <tr key={label} className="border-t border-slate-100"><td className="px-4 py-3 font-medium text-slate-700">{label}</td>{regions.map((region) => <td key={`${label}-${region}`} className="px-4 py-3 text-right">{data[label]?.[region] || 0}</td>)}<td className="px-4 py-3 text-right font-semibold">{regions.reduce((sum, region) => sum + Number(data[label]?.[region] || 0), 0)}</td></tr>)}</tbody></table></section>;
}

function transpose(regions) {
    const result = {};
    Object.entries(regions).forEach(([region, categories]) => Object.entries(categories || {}).forEach(([category, value]) => { result[category] = { ...(result[category] || {}), [region]: value }; }));
    return result;
}
