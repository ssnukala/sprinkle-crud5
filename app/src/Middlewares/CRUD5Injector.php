<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD5\Middlewares;

use UserFrosting\Sprinkle\CRUD5\Database\Models\Interfaces\CRUD5ModelInterface;
use UserFrosting\Sprinkle\CRUD5\Database\Models\CRUD5Model;
use UserFrosting\Sprinkle\CRUD5\Exceptions\CRUD5Exception;
//use UserFrosting\Sprinkle\Admin\Exceptions\RecordNotFoundException;
use UserFrosting\Sprinkle\CRUD5\Exceptions\CRUD5NotFoundException;
use UserFrosting\Sprinkle\Core\Middlewares\Injector\AbstractInjector;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Routing\RouteContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\JsonResponse;

/**
 * Route middleware to inject group when its slug is passed via placeholder in the URL or request query.
 */
class CRUD5Injector extends AbstractInjector
{
    protected string $placeholder = 'id';
    protected string $crud_slug = 'crud_slug';
    protected string $attribute = 'crudModel';

    public function __construct(
        protected CRUD5ModelInterface $crudModel,
        protected DebugLoggerInterface $debugLogger
    ) {}

    protected function getInstance(?string $slug): CRUD5ModelInterface
    {
        /*
        if (!$slug || !is_numeric($slug)) {
            throw new CRUD5Exception("Invalid or missing ID: '{$slug}'.");
        }*/

        $record = $this->crudModel->where($this->placeholder, $slug)->first();
        if (!$record) {
            //$record = new CRUD5Model([]);
            throw new CRUD5NotFoundException("No record found with ID '{$slug}' in table '{$this->crudModel->getTable()}'.");
        }
        //$this->debugLogger->debug("Line 44 - CRUD5Injector: Getting id : $slug " . $this->placeholder . " Placeholer", $record->toArray());

        return $record;
    }


    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $crud_slug = $this->getParameter($request, $this->crud_slug);
        $id = $this->getParameter($request, $this->placeholder);

        if (!$this->validateSlug($crud_slug)) {
            throw new CRUD5Exception("Invalid CRUD slug: '{$crud_slug}'.");
        }

        // Clone the model to prevent modifying a shared instance
        $this->crudModel->setTable($crud_slug);
        //$modelInstance = clone $this->crudModel;
        //$modelInstance->setTable($crud_slug);

        $this->debugLogger->debug("CRUD5Injector: Table set to '{$crud_slug}'.");

        // ⚡ Return early if model injection is all that's needed
        if ($this->shouldInjectOnly($request)) {
            $this->debugLogger->debug('Model injected successfully');
            $modelInstance = clone $this->crudModel;
            $modelInstance->setTable($crud_slug);
            $request = $request->withAttribute($this->attribute, $modelInstance);
            return $handler->handle($request);
        } else {
            try {
                $instance = $this->getInstance($id);
                $request = $request->withAttribute($this->attribute, $instance);
            } catch (CRUD5NotFoundException $e) {
                $this->debugLogger->error("CRUD5Injector: Record not found with ID '{$id}' in table '{$crud_slug}'.");
            }

            return $handler->handle($request);
        }
    }

    public function processOLD(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $crud_slug = $this->getParameter($request, $this->crud_slug);
        $id = $this->getParameter($request, $this->placeholder);

        $this->debugLogger->debug("Line 58 - CRUD5Injector: Table set to '{$crud_slug}'.");
        if (!$this->validateSlug($crud_slug)) {
            throw new CRUD5Exception("Invalid CRUD slug: '{$crud_slug}'.");
        }
        $this->crudModel->setTable($crud_slug);
        //$this->debugLogger->debug("Line 58 - CRUD5Injector: Table set to '{$crud_slug}'.");

        //$this->attribute = $crud_slug;
        try {
            $instance = $this->getInstance($id);
            $request = $request->withAttribute($this->attribute, $instance);
        } catch (CRUD5NotFoundException $e) {
            //$this->debugLogger->debug("Line 58 - CRUD5Injector: Record not found with ID '{$id}' in table '{$crud_slug}'.");
        }
        //$request = $request->withAttribute($this->attribute, $instance);
        return $handler->handle($request);
    }

    protected function getParameter(ServerRequestInterface $request, string $key): ?string
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        return $route?->getArgument($key) ?? $request->getQueryParams()[$key] ?? null;
    }

    protected function validateSlug(string $slug): bool
    {
        return preg_match('/^[a-zA-Z0-9_]+$/', $slug) === 1;
    }

    protected function shouldInjectOnly(ServerRequestInterface $request): bool
    {
        $injectonly  = $this->getParameter($request, 'inject_only');
        $this->debugLogger->debug("Line 121 : CRUD5Injector: Inject only: " . $injectonly);
        return $injectonly ? true : false;
    }
}
