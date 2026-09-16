<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Builds every "{subject}.{action}" permission and the roles defined in config/markedge.php.
 * Safe to re-run: roles are synced to the configured permission set.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $subjects = config('markedge.permissions.subjects');
        $actions = config('markedge.permissions.actions');

        $all = [];

        foreach ($subjects as $subject) {
            foreach ($actions as $action) {
                $all[] = "{$subject}.{$action}";
            }
        }

        foreach ($all as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (config('markedge.roles') as $roleName => $patterns) {
            $role = Role::findOrCreate($roleName, 'web');

            $role->syncPermissions($this->expand($patterns, $all));
        }
    }

    /**
     * @param  array<int, string>  $patterns
     * @param  array<int, string>  $all
     * @return array<int, string>
     */
    protected function expand(array $patterns, array $all): array
    {
        $granted = [];

        foreach ($patterns as $pattern) {
            if ($pattern === '*') {
                return $all;
            }

            if (str_ends_with($pattern, '.*')) {
                $subject = substr($pattern, 0, -2);
                $granted = array_merge($granted, array_values(array_filter(
                    $all,
                    fn (string $name): bool => str_starts_with($name, "{$subject}."),
                )));

                continue;
            }

            if (in_array($pattern, $all, true)) {
                $granted[] = $pattern;
            }
        }

        return array_values(array_unique($granted));
    }
}
