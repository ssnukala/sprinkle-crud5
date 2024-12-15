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
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Routing\RouteContext;

/**
 * Route middleware to inject group when it's slug is passed via placeholder in the URL or request query.
 */
class CRUD5Injector extends AbstractInjector
{
    // Route placeholder
    protected string $placeholder = 'slug';

    // Middleware attribute name.
    protected string $attribute = 'CRUD5Model';

    /**
     * Inject dependencies.
     */
    public function __construct(
        protected CRUD5ModelInterface $model,
        protected DebugLogger $logger,
        protected Request $request
    ) {

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $crudSlug = $route?->getArgument('crud_slug'); // Extract the slug
        $this->attribute = $crudSlug;
        $model->setTable($crudSlug);
        $request = $request->withAttribute($this->attribute, $this->model);
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
        $this->logger->debug("Line 47: $crmodel is " . $this->model->getTable());
        //$this->model->setTable($crmodel);
        $this->logger->debug("Line 51 after setting: $crmodel is " . $this->model->getTable());
        if (($records = $this->model->first()) === null) {
            throw new RecordNotFoundException();
        }

        // @phpstan-ignore-next-line Role Interface is a model
        return $records;
    }

    /**
     * {@inheritdoc}
     */
    public function delete__invoke(Request $request, Handler $handler)
    {
        // Extract the 'crud_slug' parameter from the route
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $crudSlug = $route?->getArgument('crud_slug'); // Extract the slug

        // Log the crud_slug for debugging
        $this->logger->debug("CRUD5Injector: crud_slug = {$crudSlug}");

        if ($crudSlug) {
            // Dynamically set the table to the CRUD slug
            $this->model->setTable($crudSlug);

            // Log the dynamically set table name
            $this->logger->debug("CRUD5Injector: Setting table to: {$crudSlug}");

            // Check if the table exists and fetch a record
            //if (!$this->model->first()) {
            //    throw new RecordNotFoundException("No records found for table: {$crudSlug}");
            //}

            // Attach the model to the request
            $request = $request->withAttribute($this->attribute, $this->model);
        }

        return $handler->handle($request);
    }
}
