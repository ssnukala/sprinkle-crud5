<?php

declare(strict_types=1);

/*
 * UserFrosting Account Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-account
 * @copyright Copyright (c) 2022 Alexander Weissman & Louis Charette
 * @license   https://github.com/userfrosting/sprinkle-account/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD5\ServicesProvider;

use UserFrosting\ServicesProvider\ServicesProviderInterface;
use UserFrosting\Sprinkle\CRUD5\Database\Models\Interfaces\CRUD5ModelInterface;
use UserFrosting\Sprinkle\CRUD5\Database\Models\CRUD5Model;

/**
 * Map models interface to the class.
 *
 * Note both class are map using class-string, since Models are not instantiated
 * by the container in the Eloquent world.
 */
class CRUD5ModelsService implements ServicesProviderInterface
{
    public function register(): array
    {
        return [
            CRUD5ModelInterface::class      => \DI\autowire(CRUD5Model::class)
        ];
    }
}