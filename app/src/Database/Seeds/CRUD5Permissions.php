<?php

declare(strict_types=1);

/*
 * UserFrosting Account Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-account
 * @copyright Copyright (c) 2013-2024 Alexander Weissman & Louis Charette
 * @license   https://github.com/userfrosting/sprinkle-account/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD5\Database\Seeds;

use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\PermissionInterface;
use UserFrosting\Sprinkle\Account\Database\Models\Permission;
use UserFrosting\Sprinkle\Account\Database\Models\Role;
use UserFrosting\Sprinkle\Core\Seeder\SeedInterface;
use UserFrosting\Sprinkle\MyEmpire\Database\Seeds\MyEmpireRoles;
use UserFrosting\Sprinkle\Core\Database\Migrator\MigrationRepositoryInterface;
use UserFrosting\Sprinkle\Account\Database\Migrations\v400\ActivitiesTable;
use UserFrosting\Sprinkle\Account\Database\Migrations\v400\GroupsTable;

/**
 * Seeder for the default permissions.
 */
class CRUD5Permissions implements SeedInterface
{
    public function __construct(
        protected MigrationRepositoryInterface $repository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        // The migration we require
        $groupMigrations = [
            ActivitiesTable::class,
            GroupsTable::class,
        ];

        foreach ($groupMigrations as $groupMigration) {
            // Make sure required migration is in the ran list. Throw exception if it isn't.
            if (!$this->repository->has($groupMigration)) {
                throw new \Exception("Migration `$groupMigration` doesn't appear to have been run!");
            }
        }
        $meperm = Permission::where('slug', 'me_user')->first()->id;

        if (!$meperm) {

            // We require the default roles seed
            (new MyEmpireRoles($this->repository))->run();

            // Get and save permissions
            $permissions = $this->getPermissions();
            $this->savePermissions($permissions);

            // Add default mappings to permissions
            $this->syncPermissionsRole($permissions);
        }
    }

    /**
     * @return Permission[] Permissions to seed
     */
    protected function getPermissions(): array
    {
        $defaultRoleIds = [
            'user'        => Role::where('slug', 'user')->first()->id, // @phpstan-ignore-line Eloquent doesn't push model to first()
            'group-admin' => Role::where('slug', 'group-admin')->first()->id, // @phpstan-ignore-line Eloquent doesn't push model to first()
            'site-admin'  => Role::where('slug', 'site-admin')->first()->id, // @phpstan-ignore-line Eloquent doesn't push model to first()
        ];

        $permissions = [
            'c5_user' => new Permission([
                'slug' => 'c5_user',
                'name' => 'User CRUD Activities',
                'conditions' => "always()",
                'description' => 'User CRUD Activities'
            ]),
            'c5_admin' => new Permission([
                'slug' => 'c5_admin',
                'name' => 'Admin CRUD Activities',
                'conditions' => "always()",
                'description' => 'Admin CRUD Activities'
            ]),
        ];
        return $permissions;
    }

    /**
     * Save permissions.
     *
     * @param array<string, PermissionInterface> $permissions
     */
    protected function savePermissions(array &$permissions): void
    {
        /** @var PermissionInterface $permission */
        foreach ($permissions as $slug => $permission) {
            // Trying to find if the permission already exist
            $existingPermission = Permission::where([
                'slug'       => $permission->slug,
                'conditions' => $permission->conditions,
            ])->first();

            // Don't save if already exist, use existing permission reference
            // otherwise to re-sync permissions and roles
            if ($existingPermission == null) {
                $permission->save();
            } else {
                $permissions[$slug] = $existingPermission;
            }
        }
    }

    /**
     * Sync permissions with default roles.
     *
     * @param Permission[] $permissions
     */
    protected function syncPermissionsRole(array $permissions): void
    {

        $roleUser = Role::where('slug', 'user')->first();
        if ($roleUser !== null) {
            $roleUser->permissions()->sync([
                $permissions['c5_user']->id
            ]);
        }

        $adminroles = [
            'site-admin',
        ];
        $defaultRoles = Role::whereIn('slug', $adminroles)->get()->keyBy('slug');


        $admins = [
            'site-admin' => $defaultRoles['site-admin'],
        ];
        $adminPermissions = Permission::where('slug', 'uri_users')->first();
        foreach ($admins as $type => $adminUser) {
            if ($adminUser) {
                $syncarr = [
                    $permissions['c5_user']->id,
                    $permissions['c5_admin']->id,
                ];
                $adminUser->permissions()->attach($syncarr);
            }
        }

        $roleuser = $defaultRoles['c5-user'];
        if ($roleuser) {
            $roleuser->permissions()->attach([
                $permissions['c5_user']->id,
            ]);
        }
        $roleta = $defaultRoles['c5-admin'];
        if ($roleta) {
            $roleta->permissions()->attach([
                $permissions['c5_admin']->id,
            ]);
        }
    }
}
