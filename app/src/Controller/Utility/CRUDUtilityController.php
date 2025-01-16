<?php

declare(strict_types=1);

/**
 * UF CRUD Sprinkle
 *
 * @link      https://github.com/ssnukala/ufsprinkle-crud
 * @copyright Copyright (c) 2018 Srinivas Nukala
 * @license   https://github.com/lcharette/ufsprinkle-crud/blob/master/LICENSE (MIT License)
 */


namespace UserFrosting\Sprinkle\CRUD5\Controller\Utility;

use UserFrosting\Sprinkle\Core\Log\DebugLogger;
use Illuminate\Database\Capsule\Manager as Capsule;
use UserFrosting\Sprinkle\Account\Database\Models\Permission;
use UserFrosting\Sprinkle\Account\Database\Models\Role;

/**
 * Controller class for center-related requests, including listing centers, CRUD for centers, etc.
 *
 * @author Srinivas Nukala
 */
class CRUDUtilityController
{

    public static function redirect($location = null, $ignoreAbort = true)
    {
        header('Connection: close');
        ob_start();
        header('Content-Length: 0');
        header('Location: ' . $location);
        ob_end_flush();
        flush();

        if ($ignoreAbort) {
            ignore_user_abort(true);
        }
    }

    public static function createCRUDPermissions($modelname, $admins = [])
    {
        $crudops = ['crud', 'list'];
        //$crudops = ['crud', 'uri'];
        if (count($admins) == 0) {
            $admins = ['group-admin', 'site-admin'];
        }
        /**
         * Add premissions for this table
         */
        // Skip this if permission already exists 
        //if (!Permission::where('slug', "cu_crud _" . $modelname)->first()) {
        if (!Permission::where('slug', "uri_" . $modelname)->first()) {
            // Add default permissions
            $syncarr = [];
            foreach ($crudops as $crudop) {
                //$index = "cu_" . $crudop . "_" . $modelname;
                $index = $crudop . "_" . $modelname;
                $permission =
                    new Permission([
                        'slug' => $index,
                        'name' => "$crudop $modelname ",
                        'conditions' => " always() ",
                        'description' => "$crudop  $modelname"
                    ]);
                $permission->save();
                $permissions[$index] = $permission;
                $syncarr[] = $permission->id;
            }
            //            Debug::debug("Line 55 the sync array is ", $syncarr);
            //            Debug::debug("Line 56 admin roles are ", $admins);
            $adminRoles = Role::whereIn('slug', $admins)->get()->keyBy('slug');
            // Add default mappings to permissions
            foreach ($adminRoles as $adminRole) {
                if ($adminRole) {
                    //$cp1 = Capsule::table('permission_roles')->where('role_id', $adminRole->id)->get()->pluck('permission_id');
                    //                    Debug::debug("Line 62 permissions for role " . $adminRole->slug, $cp1->toArray());
                    //$current_permissions = $cp1->toArray();
                    //                    $current_permissions = array_keys($adminRole->permissions()->get()->keyBy('permission_id')->toArray());
                    //                    Debug::debug("Line 62 premissions exist " . $adminRole->slug, $current_permissions);
                    //if ($current_permissions[0] != '') {
                    //$new_permissions = array_unique(array_merge($current_permissions, $syncarr));
                    //} else {
                    //$new_permissions = $syncarr;
                    //}
                    $new_permissions = $syncarr;
                    //                    Debug::debug("Line 67 new sync array is " . $adminRole->slug, $new_permissions);
                    $adminRole->permissions()->attach($new_permissions);
                }
            }
        }
    }

    public static function deleteCRUDPermissions($modelname)
    {
        $crudops = [
            'cu_create_' . $modelname,
            'cu_update_' . $modelname,
            'cu_delete_' . $modelname,
            'cu_view_' . $modelname
        ];
        /**
         * Delete premissions for this table
         */
        // Skip this if table is not empty
        $crudprem1 = Permission::whereIn('slug', $crudops)->get();
        if ($crudprem1) {
            $crudprem = $crudprem1->keyBy('id')->toArray();
            $permids = array_keys($crudprem);
            Capsule::table('permission_roles')->whereIn('permission_id', $permids)->delete();
            Permission::whereIn('id', $permids)->delete();
        }
    }

    public static function getUSLanguageArr($lModels)
    {
        $languageArr = [];

        // These are standard set of messages
        foreach ($lModels as $model => $fields) {
            $uc = strtoupper($model);
            $lc = strtolower($model);
            $sc = ucwords(str_replace("_", " ", $model));
            $languageArr[$uc] = [
                1 => "$model",
                2 => "$model" . 's',
                "ADD_NEW_" . $uc => "Add New " . $sc,
                "CREATE" => "Create $sc",
                "CREATED" => "$model for <strong>{{name}}</strong> has been successfully created",
                "CREATION_SUCCESSFUL" => "$model for <strong>{{name}}</strong> has been successfully created",
                "UPDATE_" . $uc => "Modify $sc Record",
                "UPDATED" => "$model updated successfully",
                "UPDATE_SUCCESSFUL" => "$model for <strong>{{name}}</strong> has been successfully updated",
                "DETAILS_UPDATED" => "$model details updated for member <strong>{{name}}</strong>",
                "DELETE" => "Delete $sc",
                "DELETE_CONFIRM" => "Are you sure you want to delete : $sc <strong>{{name}}</strong>?",
                "DELETE_YES" => "Yes, delete $sc",
                "DELETED" => "$model <strong>{{name}}</strong> deleted",
                "DELETION_SUCCESSFUL" => "$model <strong>{{name}}</strong> has been successfully deleted.",
                "DISABLE" => "Disable $sc",
                "DISABLE_SUCCESSFUL" => "$model record has been successfully disabled.",
                "DISABLE_FAILED" => "Failed to disable $sc record.",
                "EDIT" => "Edit $model",
                "ENABLE" => "Enable $model",
                "ENABLE_SUCCESSFUL" => "$model record has been successfully enabled.",
                "ENABLE_FAILED" => "Failed to enable $sc record.",
                "INFO_PAGE" => "$model information page for {{name}}",
                "LATEST" => "Latest $model data",
                "PAGE_DESCRIPTION" => "Provides management tools to manage $model data including the ability to list, edit details, enable/disable, and more.",
                "SUMMARY" => "$model Summary",
                "VIEW_ALL" => "View all $sc data",
                "LIST_TITLE" => "$sc Records"
            ];
            // Srinivas : removing this as there is a significant performance impact from this
            //$languageArr[$uc]['C'] = self::getModelAttributesLocale($table);
            $languageArr[$uc]['C'] = self::getClassAttributesLocale($fields);
        }

        return $languageArr;
    }

    public static function getClassAttributesLocale($fields)
    {
        $return = [];
        foreach ($fields as $column) {
            $uc = strtoupper($column);
            $lc = strtolower($column);
            $sc = ucwords(str_replace("_", " ", $column));
            $return[$uc] = $sc;
        }
        return $return;
    }

    public static function getModelAttributesLocale($table)
    {
        $return = [];
        $attr = Capsule::getSchemaBuilder()->getColumnListing($table);
        foreach ($attr as $column) {
            $uc = strtoupper($column);
            $lc = strtolower($column);
            $sc = ucwords(str_replace("_", " ", $column));
            $return[$uc] = $sc;
        }
        return $return;
    }

    public static  function randomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function randomString2($length = 10)
    {
        $characters = '@#$%^&*()!~+-0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
