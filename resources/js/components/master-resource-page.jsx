import { Head, Link, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';
import { canAccessHref } from '@/lib/permissions';
import ResourceDataTable from './resource-data-table';
import PopulationStatistics from './population-statistics';

export default function MasterResourcePage({ title, group = 'Master Data', createHref, createLabel, canCreateOverride, showStatistics = true, showStatisticCharts = true, ...tableProps }) {
    const { props } = usePage();
    const user = props?.auth?.user;
    const [indexTab, setIndexTab] = useState('data');
    const hasStatisticsTables = Boolean(props?.statistics?.rekapTables);
    const canCreate = canCreateOverride ?? (createHref ? canAccessHref(user, createHref, 'create') : false);

    return (
        <VillageDashboardLayout>
            <Head title={title} />
            <section className="mx-auto max-w-8xl space-y-5">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-medium text-cyan-600">{group}</p>
                        <h1 className="mt-1 text-2xl font-bold text-slate-800">{title}</h1>
                    </div>
                    {canCreate && (
                        <Link href={createHref} className="inline-flex items-center justify-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-700">
                            <Plus className="size-4" /> {createLabel}
                        </Link>
                    )}
                </div>
                {hasStatisticsTables && <div className="flex flex-wrap gap-2" role="tablist" aria-label={`Data dan statistik ${title.toLowerCase()}`}>
                    {[['data', 'Data'], ['rekap', 'Rekap'], ['grafik', 'Grafik']].map(([key, label]) => <button key={key} type="button" role="tab" aria-selected={indexTab === key} onClick={() => setIndexTab(key)} className={`rounded-md px-4 py-2 text-sm font-semibold ${indexTab === key ? 'bg-cyan-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50'}`}>{label}</button>)}
                </div>}
                {showStatistics && (!hasStatisticsTables || indexTab !== 'data') && (
                    <PopulationStatistics
                        statistics={props?.statistics}
                        years={props?.statisticYears}
                        selectedYear={props?.statisticYear}
                        path={typeof window !== 'undefined' ? window.location.pathname : ''}
                        showCharts={showStatisticCharts}
                        recapTab={indexTab}
                    />
                )}
                {(!hasStatisticsTables || indexTab === 'data') && <ResourceDataTable {...tableProps} />}
            </section>
        </VillageDashboardLayout>
    );
}
