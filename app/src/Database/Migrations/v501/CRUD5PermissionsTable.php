<?php

/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/licenses/UserFrosting.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD5\Database\Migrations\v501;

use Illuminate\Database\Schema\Blueprint;
use UserFrosting\Sprinkle\Account\Database\Models\Permission;
use UserFrosting\Sprinkle\Account\Database\Models\Role;
use UserFrosting\Sprinkle\Core\Database\Migration;
use UserFrosting\Sprinkle\CRUD5\Controller\Utility\CRUDUtilityController as CRUDUtil;
use UserFrosting\Sprinkle\Account\Database\Migrations\v400\PermissionsTable;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use Illuminate\Database\Schema\Builder;

/**
 * Permissions table migration
 * Permissions now replace the 'authorize_group' and 'authorize_user' tables.
 * Also, they now map many-to-many to roles.
 * Version 4.0.0
 *
 * See https://laravel.com/docs/5.4/migrations#tables
 * @author Srinivas Nukala (https://srinivasnukala.com)
 */
class CRUD5PermissionsTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public static $dependencies = [
        PermissionsTable::class, // which itself requires RolesTable and PermissionsRolesTable
    ];

    public function __construct(
        protected Builder $schema,
        protected DebugLoggerInterface $debugLogger
    ) {}


    /**
     * {@inheritdoc}
     */
    public function up(): void
    {
        if ($this->schema->hasTable('permissions')) {
            // Skip this if table is not empty
            if (!Permission::where('slug', 'c5_user')->first()) {

                // Add default permissions
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

                foreach ($permissions as $slug => $permission) {
                    $permission->save();
                    $this->debugLogger->debug("Line 77 created permission ", $permission->toArray());
                }

                // Add default mappings to permissions
                $roleUser = Role::where('slug', 'user')->first();
                if ($roleUser) {
                    $roleUser->permissions()->attach([
                        //6, // this is the update_account_settings perission every user will need
                        $permissions['c5_user']->id,
                    ]);
                }

                // Add default mappings to permissions
                $roleUser = Role::where('slug', 'site-admin')->first();
                if ($roleUser) {
                    $roleUser->permissions()->attach([
                        //6, // this is the update_account_settings perission every user will need
                        $permissions['c5_user']->id,
                        $permissions['c5_admin']->id,
                    ]);
                }
            }
            // Skip this if table is not empty
            //$adminroles = ['group-admin', 'site-admin', 'me-exec', 'me-regadmin', 'me-superadmin'];
            //CRUDUtil::createCRUDPermissions('me_member', $adminroles);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function down(): void {}
}