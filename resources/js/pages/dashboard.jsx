import VillageDashboardLayout from '@/layouts/village-dashboard-layout';
import { Head } from '@inertiajs/react';
import PopulationStatistics from '@/components/population-statistics';

export default function Dashboard({ moduleStatistics = {}, statisticYear, statisticYears = [] }) {
    return (
        <VillageDashboardLayout>
            <Head title="Dashboard" />
            <section className="mx-auto max-w-8xl space-y-8">
                <div><p className="text-sm font-medium text-cyan-600">Ringkasan Sistem</p><h1 className="mt-1 text-2xl font-bold text-slate-800">Dashboard Kependudukan</h1><p className="mt-1 text-sm text-slate-500">Ringkasan statistik dari modul yang dapat Anda akses.</p></div>
                {Object.entries(moduleStatistics).map(([module, item]) => <section key={module} className="space-y-3"><h2 className="text-xl font-bold text-slate-800">{item.title}</h2><PopulationStatistics statistics={item.statistics} years={statisticYears} selectedYear={statisticYear} path="/dashboard" /></section>)}
                {Object.keys(moduleStatistics).length === 0 && <div className="rounded-lg border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">Belum ada statistik yang dapat ditampilkan.</div>}
            </section>
        </VillageDashboardLayout>
    );
}
