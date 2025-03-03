<?php

/*
 * UserFrosting Admin Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-admin
 * @copyright Copyright (c) 2022 Alexander Weissman & Louis Charette
 * @license   https://github.com/userfrosting/sprinkle-admin/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD5;

use UserFrosting\Event\EventListenerRecipe;
use UserFrosting\Sprinkle\Account\Account;
use UserFrosting\Sprinkle\Admin\Admin;
use UserFrosting\Sprinkle\Core\Core;
use UserFrosting\Sprinkle\Account\Event\UserRedirectedAfterLoginEvent;
use UserFrosting\Sprinkle\Admin\Listener\UserRedirectedToDashboard;
use UserFrosting\Sprinkle\SprinkleRecipe;
use UserFrosting\Sprinkle\CRUD5\Routes\CRUD5Routes;
use UserFrosting\Sprinkle\CRUD5\Routes\DashboardRoutes;
use UserFrosting\Sprinkle\CRUD5\ServicesProvider\CRUD5ModelsService;
use UserFrosting\Sprinkle\CRUD5\Database\Seeds\CRUD5Permissions;
use UserFrosting\Theme\AdminLTE\AdminLTE;
use UserFrosting\Sprinkle\Core\Sprinkle\Recipe\SeedRecipe;
use UserFrosting\Sprinkle\Core\Sprinkle\Recipe\MigrationRecipe;
use UserFrosting\Sprinkle\CRUD5\Database\Migrations\v501\CRUD5PermissionsTable;

class CRUD5 implements
    SprinkleRecipe,
    EventListenerRecipe,
    MigrationRecipe,
    SeedRecipe
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'CRUD5 Sprinkle';
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        return __DIR__ . '/../';
    }

    /**
     * {@inheritdoc}
     */
    public function getSprinkles(): array
    {
        return [
            Core::class,
            Admin::class,
            AdminLTE::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRoutes(): array
    {
        return [
            CRUD5Routes::class,
            DashboardRoutes::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getServices(): array
    {
        return [
            CRUD5ModelsService::class,
        ];
    }

    public function getMigrations(): array
    {
        return [
            // v501
            CRUD5PermissionsTable::class,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    public function getSeeds(): array
    {
        return [
            //CRUD5Permissions::class,
        ];
    }

    /**
     * {@inheritDoc}
     * N.B.: Last listeners will be executed first.
     */
    public function getEventListeners(): array
    {
        return [
            UserRedirectedAfterLoginEvent::class => [
                UserRedirectedToDashboard::class,
            ],
        ];
    }
}
