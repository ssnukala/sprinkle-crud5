<?php

declare(strict_types=1);

/*
 * UserFrosting Admin Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-admin
 * @copyright Copyright (c) 2022 Alexander Weissman & Louis Charette
 * @license   https://github.com/userfrosting/sprinkle-admin/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD5\Middlewares;

use UserFrosting\Sprinkle\CRUD5\Database\Models\Interfaces\CRUD5ModelInterface;
use UserFrosting\Sprinkle\CRUD5\Exceptions\RecordNotFoundException;
use UserFrosting\Sprinkle\Core\Middlewares\Injector\AbstractInjector;

/**
 * Route middleware to inject group when it's slug is passed via placeholder in the URL or request query.
 */
class CRUD5Injector extends AbstractInjector
{
    // Route placeholder
    protected string $placeholder = 'id';

    // Middleware attribute name.
    protected string $attribute = 'CRUD5Model';

    /**
     * Inject dependencies.
     */
    public function __construct(
        protected CRUD5ModelInterface $model,
    ) {
    }

    /**
     * Returns the group's instance.
     *
     * @param string|null $slug
     *
     * @return CRUD5ModelInterface
     */
    protected function getInstance(?string $slug): CRUD5ModelInterface
    {
        if (($records = $this->model->first()) === null) {
            throw new RecordNotFoundException();
        }

        // @phpstan-ignore-next-line Role Interface is a model
        return $records;
    }
}
