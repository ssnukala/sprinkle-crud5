<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD5 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud5
 * @copyright Copyright (c) 2022 Srinivas Nukala
 * @license   https://github.com/userfrosting/sprinkle-admin/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD5\Controller\Base;

use Illuminate\Database\Connection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserFrosting\Alert\AlertStream;
use UserFrosting\Fortress\RequestSchema;
use UserFrosting\Fortress\RequestSchema\RequestSchemaInterface;
use UserFrosting\Fortress\Transformer\RequestDataTransformer;
use UserFrosting\Fortress\Validator\ServerSideValidator;
use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\CRUD5\Database\Models\Interfaces\CRUD5ModelInterface;
use UserFrosting\Sprinkle\CRUD5\Database\Models\CRUD5Model;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
use UserFrosting\Sprinkle\Account\Log\UserActivityLogger;
use UserFrosting\Sprinkle\CRUD5\Exceptions\BaseException;
use UserFrosting\Sprinkle\Core\Exceptions\ValidationException;
use UserFrosting\Support\Message\UserMessage;
use Slim\Routing\RouteContext;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;

/**
 * Processes the request to create a new Base Record.
 *
 * Processes the request from the Base Record creation form, checking that:
 * 1. validate data;
 * 2. The user has permission to create a new base record;
 * 3. The submitted data is valid.
 * This route requires authentication (and should generally be limited to admins or the root user).
 *
 * Request type: POST
 *
 * @see getModalCreateBase
 */
class BaseCreateAction
{
    // Request schema for client side form validation
    protected string $schema = 'schema://requests/group/create.yaml';
    protected string $crud_slug = 'crud_slug';
    protected string $crud_slug_attribute = 'crud_slug';
    protected string $crud_action = 'to_be_set';

    /**
     * Inject dependencies.
     */
    public function __construct(
        protected AlertStream $alert,
        protected Authenticator $authenticator,
        protected Connection $db,
        protected CRUD5ModelInterface $crudModel,
        //protected CRUD5Model $crudModel,
        protected Translator $translator,
        protected RequestDataTransformer $transformer,
        protected ServerSideValidator $validator,
        protected UserActivityLogger $userActivityLogger,
        protected DebugLoggerInterface $debugLogger
    ) {}

    /**
     * Receive the request, dispatch to the handler, and return the payload to
     * the response.
     *
     * @param Request  $request
     * @param Response $response
     */
    public function __invoke(Request $request, Response $response): Response
    {
        $this->validateAccess();
        $this->crud_slug = $this->getParameter($request, $this->crud_slug_attribute);
        $this->crud_action = $this->getParameter($request, 'crud_action');
        $file = $this->crud_action == 'create' ? '/create.yaml' : '/edit-info.yaml';
        $this->schema = 'schema://requests/' . $this->crud_slug . $file;

        $this->handle($request);
        $payload = json_encode([], JSON_THROW_ON_ERROR);
        $response->getBody()->write($payload);
        $this->debugLogger->debug("Line 90 - Invoke BaseCreatAction: Table set to." . $this->crudModel->getTable() . " Schema " . $this->schema);

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Handle the request.
     *
     * @param Request $request
     */
    protected function handle(Request $request): void
    {

        //$this->debugLogger->debug("Line 98 - BaseCreatAction: Table set to '{$crud_slug}'.");
        $this->crudModel = new CRUD5Model();
        $this->crudModel->setTable($this->crud_slug);

        // Get POST parameters.
        $params = (array) $request->getParsedBody();

        // Load the request schema
        $schema = $this->getSchema();

        // Whitelist and set parameter defaults
        $data = $this->transformer->transform($schema, $params);
        $data = $this->setDefaults($data);

        $this->crudModel->setFillable(array_keys($data));
        $this->crudModel->forceFill($data);

        //$this->debugLogger->debug("Line 115 - BaseCreatAction: Fillable Array keys - " . $this->crudModel->getTable(), array_keys($data));
        //$this->debugLogger->debug("Line 116 - Fillable Array keys - " . $this->crudModel->getTable(), $this->crudModel->getFillable());
        // Validate request data
        $this->validateData($schema, $data);

        // Get current user. Won't be null, as AuthGuard prevent it
        /** @var UserInterface */
        $currentUser = $this->authenticator->user();

        // All checks passed!  log events/activities and create base
        // Begin transaction - DB will be rolled back if an exception occurs
        //$this->debugLogger->debug("Line 119 - BaseCreatAction: Saving to '{$crud_slug}'.", $data);
        $this->db->transaction(function () use ($data, $currentUser) {
            // Create the base
            //$this->debugLogger->debug("Line 122 - BaseCreatAction: Saving to Table -" . $this->crudModel->getTable(), $this->crudModel->toArray());
            //$this->debugLogger->debug("Line 124 - BaseCreatAction: Saving to Table -" . $this->crudModel->getTable(), $this->crudModel->toArray());
            //$this->crudModel->forceFill($data);
            $this->crudModel->save();
            // Create activity record
            $this->userActivityLogger->info("User {$currentUser->user_name} created base {$this->crudModel->id}.", [
                'type'    => 'base_create',
                'user_id' => $currentUser->id,
            ]);

            $this->alert->addMessage('success', 'BASE.CREATION_SUCCESSFUL', $data);
        });
    }

    protected function setDefaults($data)
    {
        if (isset($data['user_id']) && $data['user_id'] == null) {
            $currentUser = $this->authenticator->user();
            $data['user_id'] =   $currentUser->id;
        }
        $data['created_by'] =   $currentUser->id;

        if (!isset($data['meta'])) {
            $data['meta'] = [];
        }

        if (!is_array($data['meta'])) {
            $data['meta'] = json_decode($data['meta'], true);
        }

        return $data;
    }

    /**
     * Validate access to the page.
     *
     * @throws ForbiddenException
     */
    protected function validateAccess(): void
    {
        if (!$this->authenticator->checkAccess('create_crud5_base')) {
            throw new ForbiddenException();
        }
    }

    /**
     * Load the request schema.
     *
     * @return RequestSchemaInterface
     */
    protected function getSchema(): RequestSchemaInterface
    {
        $schema = new RequestSchema($this->schema);

        return $schema;
    }

    /**
     * Validate request POST data.
     *
     * @param RequestSchemaInterface $schema
     * @param mixed[]                $data
     */
    protected function validateData(RequestSchemaInterface $schema, array $data): void
    {
        $errors = $this->validator->validate($schema, $data);
        if (count($errors) !== 0) {
            $e = new ValidationException();
            $e->addErrors($errors);

            throw $e;
        }
    }

    protected function getParameter(Request $request, string $key): ?string
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        return $route?->getArgument($key) ?? $request->getQueryParams()[$key] ?? null;
    }
}
