<?php

declare(strict_types=1);

/*
 * UserFrosting Admin Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-admin
 * @copyright Copyright (c) 2013-2024 Alexander Weissman & Louis Charette
 * @license   https://github.com/userfrosting/sprinkle-admin/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD5\Controller\Base;

use Illuminate\Database\Connection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserFrosting\Alert\AlertStream;
use UserFrosting\Config\Config;
use UserFrosting\Fortress\RequestSchema;
use UserFrosting\Fortress\RequestSchema\RequestSchemaInterface;
use UserFrosting\Fortress\Transformer\RequestDataTransformer;
use UserFrosting\Fortress\Validator\ServerSideValidator;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
//use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\GroupInterface;
use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\UserInterface;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
use UserFrosting\Sprinkle\Account\Log\UserActivityLogger;
use UserFrosting\Sprinkle\Admin\Exceptions\GroupException;
use UserFrosting\Sprinkle\Core\Exceptions\ValidationException;
use UserFrosting\Support\Message\UserMessage;
use UserFrosting\Sprinkle\CRUD5\Database\Models\Interfaces\CRUD5ModelInterface;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use Slim\Routing\RouteContext;

/**
 * Processes the request to update an existing group's details.
 *
 * Processes the request from the group update form, checking that:
 * 1. The group name/slug are not already in use;
 * 2. The user has the necessary permissions to update the posted field(s);
 * 3. The submitted data is valid.
 * This route requires authentication (and should generally be limited to admins or the root user).
 *
 * Request type: PUT
 */
class BaseEditAction
{
    // Request schema for client side form validation
    protected string $schema = 'schema://requests/tobeset.yaml';
    protected string $crud_slug_attribute = 'crud_slug';
    protected string $crud_slug = 'to_be_set';

    protected string $crud_action = 'to_be_set';
    /**
     * Inject dependencies.
     */
    public function __construct(
        protected AlertStream $alert,
        protected Authenticator $authenticator,
        protected Config $config,
        protected Connection $db,
        protected UserActivityLogger $userActivityLogger,
        protected CRUD5ModelInterface $crudModel,
        protected RequestDataTransformer $transformer,
        protected ServerSideValidator $validator,
        protected DebugLoggerInterface $debugLogger,
    ) {}

    /**
     * Receive the request, dispatch to the handler, and return the payload to
     * the response.
     *
     * @param CRUD5ModelInterface $crudModel    The group to update, injected from middleware.
     * @param Request        $request
     * @param Response       $response
     */
    public function __invoke(CRUD5ModelInterface $crudModel, Request $request, Response $response): Response
    {
        $this->crud_slug = $this->getParameter($request, $this->crud_slug_attribute);
        $this->crud_action = $this->getParameter($request, 'crud_action');
        $file = $this->crud_action == 'create' ? '/create.yaml' : '/edit-info.yaml';
        $this->schema = 'schema://requests/' . $this->crud_slug . $file;

        $this->handle($crudModel, $request);
        $payload = json_encode([], JSON_THROW_ON_ERROR);
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Handle the request.
     *
     * @param CRUD5ModelInterface $crudModel
     * @param Request        $request
     */
    protected function handle(CRUD5ModelInterface $crudModel, Request $request): void
    {
        // Get PUT parameters
        $params = (array) $request->getParsedBody();

        // Load the request schema
        //$this->crud_slug = $this->getParameter($request, $this->crud_slug_attribute);
        //$this->crud_action = $this->getParameter($request, 'crud_action');
        //$file = $this->crud_action == 'create' ? '/create.yaml' : '/edit-info.yaml';
        //$this->schema = 'schema://requests/' . $this->crud_slug . $file;
        $schema = $this->getSchema();

        // Whitelist and set parameter defaults
        $data = $this->transformer->transform($schema, $params);
        $data = $this->setDefaults($data);
        $this->debugLogger->debug("Line 92 - CRUD5EditAction:", $data);

        // Validate request data
        $this->validateData($schema, $data);
        /*
        if ($data['name'] !== $crudModel->name) {
            $this->validateGroupName($data['name']);
        }
        if ($data['slug'] !== $crudModel->slug) {
            $this->validateGroupSlug($data['slug']);
        }
*/
        // Determine targeted fields
        $fieldNames = array_keys($data);
        //foreach ($data as $name => $value) {
        //    $fieldNames[] = $name;
        //}

        // SN TODO : Change this to use CRUD5 permission instead of the Group model

        // Access-controlled resource - check that currentUser has permission to edit submitted fields for this user
        if (!$this->authenticator->checkAccess('update_group_field', [
            'group'  => $crudModel,
            'fields' => array_values(array_unique($fieldNames)),
        ])) {
            throw new ForbiddenException();
        }

        // Get current user. Won't be null, as AuthGuard prevent it
        /** @var UserInterface */
        $currentUser = $this->authenticator->user();

        // Begin transaction - DB will be rolled back if an exception occurs
        $this->db->transaction(function () use ($data, $crudModel, $currentUser) {
            // Update the user and generate success messages
            foreach ($data as $name => $value) {
                $crudModel->setAttribute($name, $value);
            }

            $crudModel->save();

            // Create activity record
            $this->userActivityLogger->info("User {$currentUser->user_name} updated details for group {$crudModel->name}.", [
                'type'    => 'group_update_info',
                'user_id' => $currentUser->id,
            ]);
        });

        $this->alert->addMessage('success', 'GROUP.UPDATE', [
            'name' => $crudModel->name,
        ]);
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

    /**
     * Get the value of a parameter from the request.
     *
     * @param Request $request
     * @param string  $key
     *
     * @return string|null
     */

    protected function getParameter(Request $request, string $key): ?string
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        return $route?->getArgument($key) ?? $request->getQueryParams()[$key] ?? null;
    }

    /**
     * Set default values for the request data.
     *
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    protected function setDefaults($data)
    {
        $currentUser = $this->authenticator->user();
        $data['updated_by'] =   $currentUser->id;
        if (!is_array($data['meta'])) {
            $data['meta'] = json_decode($data['meta'], true);
        }
        return $data;
    }


    /*
    /**
     * Validate group name is not already in use.
     *
     * @param string $name
     * /
    protected function validateGroupName(string $name): void
    {
        $group = $this->crudModel->where('name', $name)->first();
        if ($group !== null) {
            $e = new GroupException();
            $message = new UserMessage('GROUP.NAME_IN_USE', ['name' => $name]);
            $e->setDescription($message);

            throw $e;
        }
    }

    /**
     * Validate group name is not already in use.
     *
     * @param string $slug
     * /
    protected function validateGroupSlug(string $slug): void
    {
        $group = $this->crudModel->where('slug', $slug)->first();
        if ($group !== null) {
            $e = new GroupException();
            $message = new UserMessage('SLUG_IN_USE', ['slug' => $slug]);
            $e->setDescription($message);

            throw $e;
        }
    }
    */
}
