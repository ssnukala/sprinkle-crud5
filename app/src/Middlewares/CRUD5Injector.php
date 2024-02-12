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
use UserFrosting\Sprinkle\Core\Log\DebugLogger;

/**
 * Route middleware to inject group when it's slug is passed via placeholder in the URL or request query.
 */
class CRUD5Injector extends AbstractInjector
{
    // Route placeholder
    protected string $placeholder = 'crmodel';

    // Middleware attribute name.
    protected string $attribute = 'CRUD5Model';

    /**
     * Inject dependencies.
     */
    public function __construct(
        protected CRUD5ModelInterface $model,
        protected DebugLogger $logger,
    ) {
    }

    /**
     * Returns the group's instance.
     *
     * @param string|null $cr5model
     *
     * @return CRUD5ModelInterface
     */
    protected function getInstance(?string $crmodel): CRUD5ModelInterface
    {
        $this->logger->debug("Line 47: $crmodel is " . $this->model->getModel());
        $this->model->setTable($crmodel);
        if (($records = $this->model->first()) === null) {
            throw new RecordNotFoundException();
        }

        // @phpstan-ignore-next-line Role Interface is a model
        return $records;
    }
}
