<?php

use App\Support\ModulePermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $canonical = collect(ModulePermissions::GROUPS)
            ->flatMap(fn ($items) => collect($items)->flatMap(
                fn ($title) => collect(['view', 'create', 'edit', 'delete'])->map(fn ($action) => "$action ".strtolower($title)),
            ))
            ->map('strtolower');

        $legacyIds = DB::table('permissions')
            ->whereIn(DB::raw('LOWER(name)'), ModulePermissions::managedPermissionNames()->diff($canonical)->all())
            ->pluck('id');

        if ($legacyIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $legacyIds)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $legacyIds)->delete();
        }
    }

    public function down(): void
    {
        // Legacy alias assignments cannot be reconstructed safely.
    }
};
