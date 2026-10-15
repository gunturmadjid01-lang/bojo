export function formatRupiah(value) {
    if (value === null || value === undefined || value === '') return '';
    const [integer, decimal] = String(value).replace(/[^0-9.,-]/g, '').replace(',', '.').split('.');
    const formatted = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(integer || 0));
    return `Rp ${formatted}${decimal !== undefined ? `,${decimal.slice(0, 2)}` : ''}`;
}

export function parseRupiah(value) {
    const normalized = String(value).replace(/[^0-9,.-]/g, '').replace(/\./g, '').replace(',', '.');
    return normalized;
}

export default function InputUang({ value, onChange, name, id = name, className = '', ...props }) {
    return <input {...props} id={id} name={name} inputMode="decimal" value={formatRupiah(value)} onChange={(event) => onChange(parseRupiah(event.target.value))} className={className} />;
}
