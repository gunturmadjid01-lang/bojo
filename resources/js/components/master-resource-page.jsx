import { Head, Link, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';
import { canAccessHref } from '@/lib/permissions';
import ResourceDataTable from './resource-data-table';
import PopulationStatistics from './population-statistics';

export default function MasterResourcePage({ title, group = 'Master Data', createHref, createLabel, canCreateOverride, showStatistics = true, showStatisticCharts = true, ...tableProps }) {
    const { props } = usePage();
    const user = props?.auth?.user;
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
                {showStatistics && (
                    <PopulationStatistics
                        statistics={props?.statistics}
                        years={props?.statisticYears}
                        selectedYear={props?.statisticYear}
                        path={typeof window !== 'undefined' ? window.location.pathname : ''}
                        showCharts={showStatisticCharts}
                    />
                )}
                <ResourceDataTable {...tableProps} />
            </section>
        </VillageDashboardLayout>
    );
}
