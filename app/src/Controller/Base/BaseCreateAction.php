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
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
use UserFrosting\Sprinkle\Account\Log\UserActivityLogger;
use UserFrosting\Sprinkle\CRUD5\Exceptions\BaseException;
use UserFrosting\Sprinkle\Core\Exceptions\ValidationException;
use UserFrosting\Support\Message\UserMessage;

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
    protected string $schema = 'schema://requests/base/create.yaml';

    /**
     * Inject dependencies.
     */
    public function __construct(
        protected AlertStream $alert,
        protected Authenticator $authenticator,
        protected Connection $db,
        protected CRUD5ModelInterface $baseModel,
        protected Translator $translator,
        protected RequestDataTransformer $transformer,
        protected ServerSideValidator $validator,
        protected UserActivityLogger $userActivityLogger,
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
        $this->handle($request);
        $payload = json_encode([], JSON_THROW_ON_ERROR);
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Handle the request.
     *
     * @param Request $request
     */
    protected function handle(Request $request): void
    {
        // Get POST parameters.
        $params = (array) $request->getParsedBody();

        // Load the request schema
        $schema = $this->getSchema();

        // Whitelist and set parameter defaults
        //$transformer = new RequestDataTransformer($schema);
        $data = $this->transformer->transform($schema, $params);

        // Validate request data
        $this->validateData($schema, $data);

        // Get current user. Won't be null, as AuthGuard prevent it
        /** @var UserInterface */
        $currentUser = $this->authenticator->user();

        // All checks passed!  log events/activities and create base
        // Begin transaction - DB will be rolled back if an exception occurs
        $this->db->transaction(function () use ($data, $currentUser) {
            // Create the base
            $base = new $this->baseModel($data);
            $base->save();

            // Create activity record
            $this->userActivityLogger->info("User {$currentUser->user_name} created base {$base->id}.", [
                'type'    => 'base_create',
                'user_id' => $currentUser->id,
            ]);

            $this->alert->addMessage('success', 'BASE.CREATION_SUCCESSFUL', $data);
        });
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
}
