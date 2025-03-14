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
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserFrosting\Alert\AlertStream;
use UserFrosting\Config\Config;
use UserFrosting\Fortress\RequestSchema;
use UserFrosting\Fortress\RequestSchema\RequestSchemaInterface;
use UserFrosting\Fortress\Transformer\RequestDataTransformer;
use UserFrosting\Fortress\Validator\ServerSideValidator;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
//use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\RoleInterface;
use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\UserInterface;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
use UserFrosting\Sprinkle\Account\Log\UserActivityLogger;
use UserFrosting\Sprinkle\Admin\Exceptions\MissingRequiredParamException;
use UserFrosting\Sprinkle\Core\Exceptions\ValidationException;
use UserFrosting\Sprinkle\CRUD5\Database\Models\Interfaces\CRUD5ModelInterface;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;

/**
 * Processes the request to update a specific field for an existing role, including permissions.
 *
 * Processes the request from the role update form, checking that:
 * 1. The logged-in user has the necessary permissions to update the putted field(s);
 * 2. The submitted data is valid.
 * This route requires authentication.
 *
 * Request type: PUT
 */
class BaseUpdateFieldAction
{
    // Request schema for client side form validation
    protected string $schema = 'schema://requests/role/edit-field.yaml';

    /**
     * Inject dependencies.
     */
    public function __construct(
        protected AlertStream $alert,
        protected Authenticator $authenticator,
        protected Config $config,
        protected Connection $db,
        protected UserActivityLogger $userActivityLogger,
        protected RequestDataTransformer $transformer,
        protected ServerSideValidator $validator,
        protected DebugLoggerInterface $debugLogger,
    ) {}

    /**
     * Receive the request, dispatch to the handler, and return the payload to
     * the response.
     *
     * @param RoleInterface $role     The role to update, injected by middleware.
     * @param string        $field    The field to update.
     * @param Request       $request
     * @param Response      $response
     */
    public function __invoke(
        CRUD5ModelInterface $crud5,
        string $field,
        Request $request,
        Response $response
    ): Response {
        $this->handle($crud5, $field, $request);
        $payload = json_encode([], JSON_THROW_ON_ERROR);
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Handle the request.
     *
     * @param CRUD5ModelInterface $crud5
     * @param string        $fieldName
     * @param Request       $request
     */
    protected function handle(
        CRUD5ModelInterface $crud5,
        string $fieldName,
        Request $request
    ): void {
        // Access-controlled resource - check that current User has permission
        // to edit the specified field for this user
        $this->validateAccess($crud5, $fieldName);

        // Get current user. Won't be null, as AuthGuard prevent it
        /** @var UserInterface */
        $currentUser = $this->authenticator->user();

        // Get PUT parameters: value
        $put = (array) $request->getParsedBody();

        // Make sure data is part of $_PUT data.
        // Except for roles, which we allows to be empty.
        if (isset($put[$fieldName])) {
            $fieldData = $put[$fieldName];
        } elseif ($fieldName === 'permissions') {
            $fieldData = $put['value'] ?? [];
        } else {
            $e = new MissingRequiredParamException();
            $e->setParam($fieldName);

            throw $e;
        }

        // Create and validate key -> value pair
        $params = [
            $fieldName => $fieldData,
        ];

        // Load the request schema
        $schema = $this->getSchema();

        // Whitelist and set parameter defaults
        $data = $this->transformer->transform($schema, $params);

        // Validate request data
        $this->validateData($schema, $data);

        // Get validated and transformed value
        $fieldValue = $data[$fieldName];

        // Begin transaction - DB will be rolled back if an exception occurs
        $this->db->transaction(function () use ($fieldName, $fieldValue, $crud5, $currentUser) {
            if ($fieldName === 'permissions') {
                $collection = new Collection($fieldValue);
                $newPermissions = $collection->pluck('permission_id')->all();
                $crud5->permissions()->sync($newPermissions);
            } else {
                $crud5->$fieldName = $fieldValue; // @phpstan-ignore-line Variable property is ok here.
                $crud5->save();
            }

            // Create activity record
            $this->userActivityLogger->info("User {$currentUser->user_name} updated property '$fieldName' for role {$crud5->name}.", [
                'type'    => 'role_update_field',
                'user_id' => $currentUser->id,
            ]);
        });

        // Add success messages
        if ($fieldName === 'permissions') {
            $this->alert->addMessage('success', 'ROLE.PERMISSIONS_UPDATED', [
                'name' => $crud5->name,
            ]);
        } else {
            $this->alert->addMessage('success', 'ROLE.UPDATED', [
                'name' => $crud5->name,
            ]);
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

    /**
     * Validate access to the page.
     *
     * @throws ForbiddenException
     */
    protected function validateAccess(CRUD5ModelInterface $crud5, string $fieldName): void
    {
        if (!$this->authenticator->checkAccess('update_role_field', [
            'role'   => $crud5,
            'fields' => [$fieldName],
        ])) {
            throw new ForbiddenException();
        }
    }
}
