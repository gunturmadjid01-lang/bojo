import DataTable from '@/components/data-table';

export default function VillageDashboard({ data = [], onAdd, onView, onEdit, onDelete }) {
    return <DataTable data={data} onAdd={onAdd} onView={onView} onEdit={onEdit} onDelete={onDelete} />;
}
