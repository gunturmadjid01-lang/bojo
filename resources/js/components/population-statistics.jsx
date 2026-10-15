import { router } from '@inertiajs/react';
import { BarChart3, CalendarDays, UsersRound } from 'lucide-react';
import { Bar, BarChart, CartesianGrid, Cell, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const numberFormatter = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
const chartColors = ['#0891b2', '#0e7490', '#14b8a6', '#f59e0b', '#f97316', '#ef4444', '#8b5cf6', '#64748b'];

const demographicTitles = {
    age: 'Penduduk Berdasarkan Kelompok Usia',
    gender: 'Penduduk Berdasarkan Jenis Kelamin',
    blood: 'Penduduk Berdasarkan Golongan Darah',
    religion: 'Penduduk Berdasarkan Agama',
    education: 'Penduduk Berdasarkan Pendidikan',
    occupation: 'Penduduk Berdasarkan Pekerjaan',
    marital: 'Penduduk Berdasarkan Status Perkawinan',
    citizenship: 'Penduduk Berdasarkan Kewarganegaraan',
    status: 'Status Penduduk',
};

function DemographicChart({ title, data, bar = false }) {
    const chartData = (data || []).map((item) => ({ label: item.label, value: Number(item.value || 0) }));
    const hasData = chartData.some((item) => item.value > 0);

    return (
        <Card className="border-slate-200 shadow-sm">
            <CardHeader className="p-4 pb-2"><CardTitle className="text-base text-slate-800">{title}</CardTitle></CardHeader>
            <CardContent className="p-4 pt-2">
                {!hasData ? <div className="flex h-full items-center justify-center text-sm text-slate-500">Belum ada data statistik.</div> : (
                    bar ? (
                        <div className="h-80 w-full">
                            <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={chartData} layout="vertical" margin={{ top: 8, right: 12, left: 12, bottom: 0 }}>
                                <CartesianGrid strokeDasharray="3 3" horizontal={false} stroke="#e2e8f0" />
                                <XAxis type="number" allowDecimals={false} tick={{ fontSize: 22, fill: '#64748b' }} />
                                <YAxis type="category" dataKey="label" width={200} tick={{ fontSize: 20, fill: '#64748b' }} />
                                <Tooltip formatter={(value) => [numberFormatter.format(value), 'Jumlah']} />
                                <Bar dataKey="value" fill="#0891b2" radius={[0, 4, 4, 0]} />
                            </BarChart>
                            </ResponsiveContainer>
                        </div>
                    ) : (
                        <div className="w-full space-y-3">
                            <div className="h-64 w-full">
                                <ResponsiveContainer width="100%" height="100%">
                                <PieChart>
                                <Pie data={chartData} dataKey="value" nameKey="label" cx="50%" cy="50%" innerRadius={58} outerRadius={96} paddingAngle={2}>
                                    {chartData.map((item, index) => <Cell key={`${item.label}-${index}`} fill={chartColors[index % chartColors.length]} />)}
                                </Pie>
                                <Tooltip formatter={(value) => [numberFormatter.format(value), 'Jumlah']} />
                            </PieChart>
                                </ResponsiveContainer>
                            </div>
                            <div className="flex w-full gap-3 overflow-x-auto pb-2" aria-label={`Legenda ${title}`}>
                                {chartData.map((item, index) => <div key={`${item.label}-legend`} className="flex shrink-0 items-center gap-2 rounded-md bg-slate-50 px-3 py-2 text-sm text-slate-700"><span className="size-3 rounded-sm" style={{ backgroundColor: chartColors[index % chartColors.length] }} /><span>{item.label}: {numberFormatter.format(item.value)}</span></div>)}
                            </div>
                        </div>
                    )
                )}
            </CardContent>
        </Card>
    );
}

export default function PopulationStatistics({ statistics, years = [], selectedYear, path, showCharts = true }) {
    if (!statistics) return null;

    const monthlyData = (statistics.months || []).map((month, index) => ({
        month,
        total: Number(statistics.monthly?.[index] || 0),
    }));
    const regionData = (statistics.regionValues || []).map((item) => ({
        label: item.label,
        total: Number(item.value || 0),
    }));

    const changeYear = (event) => {
        const isDashboard = path === '/dashboard';
        router.get(path, { statistik_tahun: event.target.value }, {
            ...(isDashboard ? {} : { only: ['statistics', 'statisticYears', 'statisticYear'] }),
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    return (
        <div className="space-y-5">
            <div className="flex flex-col gap-2 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <CalendarDays className="size-4 text-cyan-600" />
                    <label htmlFor="statistik-tahun">Tahun Statistik</label>
                </div>
                <select id="statistik-tahun" value={selectedYear || ''} onChange={changeYear} className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">
                    {years.map((year) => <option key={year} value={year}>{year}</option>)}
                </select>
            </div>

            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                {statistics.cards?.map((card) => (
                    <Card key={card.label} className="border-slate-200 shadow-sm">
                        <CardContent className="flex items-center justify-between gap-3 p-4">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{card.label}</p>
                                <p className="mt-2 text-2xl font-bold text-slate-800">{numberFormatter.format(Number(card.value || 0))}</p>
                            </div>
                            <div className="flex size-10 shrink-0 items-center justify-center rounded-md bg-cyan-50 text-cyan-600">
                                <UsersRound className="size-5" />
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>

            {showCharts && <div className="grid gap-5 xl:grid-cols-2">
                <Card className="border-slate-200 shadow-sm">
                    <CardHeader className="p-4 pb-2"><CardTitle className="flex items-center gap-2 text-base text-slate-800"><BarChart3 className="size-4 text-cyan-600" />{statistics.monthlyTitle}</CardTitle>{statistics.monthlySubtitle && <p className="text-xs text-slate-500">{statistics.monthlySubtitle}</p>}</CardHeader>
                    <CardContent className="h-72 p-4 pt-2">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={monthlyData} margin={{ top: 8, right: 8, left: -20, bottom: 0 }}>
                                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                                <XAxis dataKey="month" tick={{ fontSize: 24, fill: '#64748b' }} />
                                <YAxis allowDecimals={false} tick={{ fontSize: 24, fill: '#64748b' }} />
                                <Tooltip formatter={(value) => [numberFormatter.format(value), 'Jumlah']} />
                                <Bar dataKey="total" fill="#0891b2" radius={[4, 4, 0, 0]} />
                            </BarChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>

                <Card className="border-slate-200 shadow-sm">
                    <CardHeader className="p-4 pb-2"><CardTitle className="flex items-center gap-2 text-base text-slate-800"><BarChart3 className="size-4 text-cyan-600" />{statistics.regionTitle}</CardTitle></CardHeader>
                    <CardContent className="h-72 p-4 pt-2">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={regionData} layout="vertical" margin={{ top: 8, right: 12, left: 12, bottom: 0 }}>
                                <CartesianGrid strokeDasharray="3 3" horizontal={false} stroke="#e2e8f0" />
                                <XAxis type="number" allowDecimals={false} tick={{ fontSize: 24, fill: '#64748b' }} />
                                <YAxis type="category" dataKey="label" width={144} tick={{ fontSize: 22, fill: '#64748b' }} />
                                <Tooltip formatter={(value) => [numberFormatter.format(value), 'Jumlah']} />
                                <Bar dataKey="total" fill="#0e7490" radius={[0, 4, 4, 0]} />
                            </BarChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>
            </div>}

            {showCharts && statistics.demographics && (
                <section className="space-y-3">
                    <div>
                        <h2 className="text-lg font-bold text-slate-800">Demografi Penduduk</h2>
                        <p className="text-sm text-slate-500">Kondisi penduduk aktif saat ini dalam cakupan penugasan.</p>
                    </div>
                    <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        {Object.entries(statistics.demographics).map(([key, data]) => (
                            <DemographicChart key={key} title={demographicTitles[key] || key} data={data} bar={key === 'education' || key === 'occupation'} />
                        ))}
                    </div>
                </section>
            )}
        </div>
    );
}
