<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD5 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-crud5
 * @copyright Copyright (c) 2022 Srinvas Nukala
 * @license   https://github.com/userfrosting/sprinkle-admin/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD5\Routes;

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use UserFrosting\Routes\RouteDefinitionInterface;
use UserFrosting\Sprinkle\Account\Authenticate\AuthGuard;
use UserFrosting\Sprinkle\CRUD5\Controller\Base\BasePageAction;
use UserFrosting\Sprinkle\CRUD5\Controller\Base\BasePageListAction;
use UserFrosting\Sprinkle\CRUD5\Controller\Base\BaseDeleteAction;
use UserFrosting\Sprinkle\CRUD5\Controller\Base\BaseCreateAction;
use UserFrosting\Sprinkle\CRUD5\Controller\Base\BaseEditAction;
use UserFrosting\Sprinkle\CRUD5\Controller\Base\BaseUpdateFieldAction;
use UserFrosting\Sprinkle\CRUD5\Controller\Base\BaseCreateModal;
use UserFrosting\Sprinkle\CRUD5\Controller\Base\BaseDeleteModal;
use UserFrosting\Sprinkle\CRUD5\Controller\Base\BaseEditModal;

use UserFrosting\Sprinkle\CRUD5\Middlewares\CRUD5Injector;
use UserFrosting\Sprinkle\Core\Middlewares\NoCache;

/*
 * Routes for administrative user management.
 */

class CRUD5Routes implements RouteDefinitionInterface
{
      public function register(App $app): void
      {
            $app->group('/crud5/{crud_slug}', function (RouteCollectorProxy $group) {
                  $group->get('', BasePageListAction::class)
                        ->setName('crud5-model');
                  $group->get('/r/{id}', BasePageAction::class)
                        ->add(CRUD5Injector::class)
                        ->setName('crud5.record');
            })->add(AuthGuard::class)->add(NoCache::class);

            $app->group('/api/crud5/{crud_slug}', function (RouteCollectorProxy $group) {
                  $group->get('', [BasePageListAction::class, 'sprunje'])
                        ->setName('api_crud5');
                  $group->delete('/r/{id}', BaseDeleteAction::class)
                        ->add(CRUD5Injector::class)
                        ->setArgument('crud_action', 'delete')
                        ->setName('api.crud5.delete');
                  $group->post('', BaseCreateAction::class)
                        ->setArgument('crud_action', 'create')
                        ->setName('api.crud5.create');
                  $group->put('/r/{id}', BaseEditAction::class)
                        ->add(CRUD5Injector::class)
                        ->setArgument('crud_action', 'edit')
                        ->setName('api.crud5.edit');
                  $group->put('/r/{id}/{field}', BaseUpdateFieldAction::class)
                        ->add(CRUD5Injector::class)
                        ->setArgument('crud_action', 'edit_field')
                        ->setName('api.crud5.update-field');
            })->add(AuthGuard::class)->add(NoCache::class);

            $app->group('/modals/crud5/{crud_slug}', function (RouteCollectorProxy $group) {
                  $group->get('/confirm-delete', BaseDeleteModal::class)
                        ->add(CRUD5Injector::class)
                        ->setArgument('crud_action', 'confirm-delete')
                        ->setName('modal.crud5.delete');
                  $group->get('/create', BaseEditModal::class)
                        ->add(CRUD5Injector::class)
                        ->setArgument('crud_action', 'create')
                        ->setName('modal.crud5.create');
                  $group->get('/edit', BaseEditModal::class)
                        ->add(CRUD5Injector::class)
                        ->setArgument('crud_action', 'edit')
                        ->setName('modal.crud5.edit');
            })->add(AuthGuard::class)->add(NoCache::class);
      }
}
