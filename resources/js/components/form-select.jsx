import Select from 'react-select';

export default function FormSelect({
    value,
    options = [],
    onChange,
    placeholder = 'Pilih opsi',
    isClearable = false,
    isSearchable = true,
    error,
    className = '',
    menuPortalTarget = null,
    isDisabled = false,
}) {
    const selectedValue = options.find((option) => String(option.value) === String(value)) || null;

    return (
        <Select
            value={selectedValue}
            options={options}
            onChange={(selected) => onChange(selected?.value ?? '')}
            placeholder={placeholder}
            isClearable={isClearable}
            isSearchable={isSearchable}
            isDisabled={isDisabled}
            menuPortalTarget={menuPortalTarget}
            className={className}
            classNamePrefix="custom-react-select"
            styles={{
                control: (base, state) => ({
                    ...base,
                    minHeight: '44px',
                    borderRadius: '0.75rem',
                    borderColor: error ? '#fca5a5' : state.isFocused ? '#67e8f9' : '#e2e8f0',
                    boxShadow: state.isFocused ? '0 0 0 2px rgba(34, 211, 238, 0.15)' : 'none',
                    backgroundColor: '#ffffff',
                    '&:hover': {
                        borderColor: error ? '#fca5a5' : '#bae6fd',
                    },
                }),
                valueContainer: (base) => ({
                    ...base,
                    padding: '0 0.75rem',
                }),
                input: (base) => ({
                    ...base,
                    fontSize: '0.875rem',
                    color: '#0f172a',
                }),
                placeholder: (base) => ({
                    ...base,
                    color: '#94a3b8',
                    fontSize: '0.875rem',
                }),
                singleValue: (base) => ({
                    ...base,
                    color: '#0f172a',
                    fontSize: '0.875rem',
                }),
                menu: (base) => ({
                    ...base,
                    borderRadius: '0.75rem',
                    overflow: 'hidden',
                    borderColor: '#e2e8f0',
                    boxShadow: '0 10px 20px rgba(15, 23, 42, 0.08)',
                    zIndex: 60,
                }),
                option: (base, state) => ({
                    ...base,
                    backgroundColor: state.isFocused ? '#e0f2fe' : state.isSelected ? '#06b6d4' : '#ffffff',
                    color: state.isSelected ? '#ffffff' : '#0f172a',
                    fontSize: '0.875rem',
                }),
                indicatorSeparator: () => ({ display: 'none' }),
                dropdownIndicator: (base, state) => ({
                    ...base,
                    color: state.isFocused ? '#0891b2' : '#64748b',
                    padding: '0 0.5rem',
                }),
                clearIndicator: (base) => ({
                    ...base,
                    color: '#64748b',
                    padding: '0 0.5rem',
                }),
            }}
        />
    );
}
