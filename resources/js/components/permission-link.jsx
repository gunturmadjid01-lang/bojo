import { Link, usePage } from '@inertiajs/react';
import { canAccessHref } from '@/lib/permissions';

export default function PermissionLink({ href, action, children, ...props }) {
    const user = usePage().props?.auth?.user;
    const inferredAction = action || (/\/create(?:\?|$)/.test(href) ? 'create' : /\/edit(?:\?|$)/.test(href) ? 'edit' : 'view');

    if (!canAccessHref(user, href, inferredAction)) return null;

    return <Link href={href} {...props}>{children}</Link>;
}
