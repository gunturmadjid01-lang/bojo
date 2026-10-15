const permissionVariants = {
    desa: ['desa', 'desas'],
    dusun: ['dusun', 'dusuns'],
    penduduk: ['penduduk', 'penduduks'],
    'kartu-keluarga': ['kartu keluarga', 'kartu_keluargas'],
    'anggota-kartu-keluarga': ['anggota kartu keluarga', 'anggota_kartu_keluargas'],
    'kelahiran': ['kelahiran', 'kelahirans'],
    'kematian': ['kematian', 'kematians'],
    'penduduk-datang': ['penduduk datang', 'penduduk_datangs', 'penduduk-datang'],
    'penduduk-pindah': ['penduduk pindah', 'penduduk_pindahs', 'penduduk-pindah'],
    'sosial-ekonomi-keluarga': ['sosial ekonomi keluarga', 'sosial_ekonomi_keluargas', 'sosial-ekonomi-keluarga'],
    'sosial-ekonomi-penduduk': ['sosial ekonomi penduduk', 'sosial_ekonomi_penduduks', 'sosial-ekonomi-penduduk'],
    'disabilitas-penduduk': ['disabilitas penduduk', 'disabilitas_penduduks', 'disabilitas-penduduk'],
    'penyaluran-bantuan': ['penyaluran bantuan', 'penyaluran_bantuans', 'penyaluran-bantuan'],
    'penerima-bantuan': ['penerima bantuan', 'penerima_bantuans', 'penerima-bantuan'],
    'program-bantuan': ['program bantuan', 'program_bantuans', 'program-bantuan'],
    pendidikan: ['pendidikan', 'pendidikans'],
    pekerjaan: ['pekerjaan', 'pekerjaans'],
    disabilitas: ['disabilitas', 'jenis disabilitas'],
    'jabatan-desa': ['jabatan desa', 'jabatan_desas', 'jabatan-desa'],
    users: ['users'],
    permissions: ['permissions'],
    reports: ['reports', 'laporan', 'laporan & statistik'],
    laporan: ['reports', 'laporan', 'laporan & statistik'],
    'penugasan-perangkat': ['penugasan perangkat', 'penugasan_perangkats', 'penugasan-perangkat'],
};

const normalize = (value) => String(value ?? '').trim().toLowerCase().replace(/[_-]+/g, ' ');

const asArray = (value) => {
    if (Array.isArray(value)) return value;
    if (value == null || value === '') return [];
    if (typeof value === 'object') {
        if (typeof value.map === 'function') return value.map((item) => item);
        if (typeof value.values === 'function') return Array.from(value.values());
        if (typeof value.forEach === 'function') {
            const result = [];
            value.forEach((item) => result.push(item));
            return result;
        }
    }
    return [value];
};

const toNameValues = (value) => {
    if (Array.isArray(value)) {
        return value.flatMap((item) => toNameValues(item));
    }

    if (value && typeof value === 'object') {
        return [value.name, value.label, value.slug, value.permission, value.title].filter(Boolean);
    }

    return [value].filter(Boolean);
};

export function getModuleFromHref(href = '') {
    if (!href) return '';

    const cleaned = href.split('?')[0].replace(/^\/+/, '');
    const raw = cleaned.split('/')[0] || '';

    return raw || '';
}

export function buildPermissionNames(module, action) {
    const aliases = permissionVariants[module] || [module];

    return [
        `${action} ${module}`,
        `${action} ${module}s`,
        ...aliases.map((alias) => `${action} ${alias}`),
        ...aliases.map((alias) => `${action} ${alias.replace(/\s+/g, '_')}`),
    ].map((value) => normalize(value));
}

export function hasPermission(user, module, action = 'view') {
    if (!user) return false;

    const rawRoles = user.roles ?? user.role ?? user.role_names ?? [];
    const roleNames = asArray(rawRoles)
        .flatMap((role) => toNameValues(role))
        .map((role) => normalize(role));

    const rawPermissions = user.permissions ?? user.all_permissions ?? user.permission_names ?? [];
    const permissionNames = asArray(rawPermissions)
        .flatMap((permission) => toNameValues(permission))
        .map((permission) => normalize(permission));

    if (roleNames.some((role) => role === 'super admin' || role === 'super administrator')) return true;

    const names = buildPermissionNames(module, action);

    return names.some((name) => permissionNames.includes(name));
}

export function hasPermissionName(user, permission) {
    if (!user) return false;

    const roleNames = asArray(user.roles ?? user.role ?? user.role_names ?? [])
        .flatMap((role) => toNameValues(role))
        .map((role) => normalize(role));

    if (roleNames.some((role) => role === 'super admin' || role === 'super administrator' || role === 'superadmin')) return true;

    const permissionNames = asArray(user.permissions ?? user.all_permissions ?? user.permission_names ?? [])
        .flatMap((item) => toNameValues(item))
        .map((item) => normalize(item));

    return permissionNames.includes(normalize(permission));
}

export function canAccessHref(user, href, action = 'view') {
    const module = getModuleFromHref(href);
    if (!module || module === 'dashboard' || module === 'logout' || module === 'settings') {
        return true;
    }
    if (module === 'desa' && action === 'view') return true;

    return hasPermission(user, module, action);
}

export function canAccessAnyActionHref(user, href) {
    const module = getModuleFromHref(href);
    if (!module || module === 'dashboard' || module === 'logout' || module === 'settings') return true;

    return ['view', 'create', 'edit', 'delete'].some((action) => hasPermission(user, module, action));
}
