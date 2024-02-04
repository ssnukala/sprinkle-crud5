<?php

declare(strict_types=1);

/*
 * UserFrosting Admin Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-admin
 * @copyright Copyright (c) 2022 Alexander Weissman & Louis Charette
 * @license   https://github.com/userfrosting/sprinkle-admin/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD5\Routes;

use Slim\App;
use UserFrosting\Routes\RouteDefinitionInterface;
use UserFrosting\Sprinkle\Account\Authenticate\AuthGuard;
use UserFrosting\Sprinkle\CRUD5\Controller\Dashboard\CacheApiAction;
use UserFrosting\Sprinkle\CRUD5\Controller\Dashboard\CacheModalAction;
use UserFrosting\Sprinkle\CRUD5\Controller\Dashboard\DashboardAction;
use UserFrosting\Sprinkle\Core\Middlewares\NoCache;

/*
 * Routes for dashboard page.
 */

class DashboardRoutes implements RouteDefinitionInterface
{
    public function register(App $app): void
    {
        $app->get('/dashboard/crud5', DashboardAction::class)
            ->setName('dashboard')
            ->add(AuthGuard::class)->add(NoCache::class);

        $app->post('/api/crud5/dashboard/clear-cache', CacheApiAction::class)
            ->add(AuthGuard::class)->add(NoCache::class);

        $app->get('/modals/crud5/dashboard/clear-cache', CacheModalAction::class)
            ->add(AuthGuard::class)->add(NoCache::class);
    }
}
