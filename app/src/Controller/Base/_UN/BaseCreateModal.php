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

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use UserFrosting\Fortress\Adapter\JqueryValidationArrayAdapter;
use UserFrosting\Fortress\RequestSchema;
use UserFrosting\Fortress\RequestSchema\RequestSchemaInterface;
use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\GroupInterface;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;

use UserFrosting\Sprinkle\CRUD5\Database\Models\Interfaces\CRUD5ModelInterface;

/**
 * Renders the modal form for creating a new group.
 *
 * This does NOT render a complete page.  Instead, it renders the HTML for the modal, which can be embedded in other pages.
 * This page requires authentication.
 *
 * Request type: GET
 */
class BaseCreateModal
{
    /** @var string Page template */
    protected string $template = 'modals/group.html.twig';

    // Request schema for client side form validation
    protected string $schema = 'schema://requests/group/create.yaml';

    // Default group icon
    protected string $defaultIcon = 'fa-solid fa-user';

    /**
     * Inject dependencies.
     */
    public function __construct(
        protected Authenticator $authenticator,
        protected CRUD5ModelInterface $crudModel,
        protected Translator $translator,
        protected Twig $view,
        protected JqueryValidationArrayAdapter $adapter,
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
        $payload = $this->handle();

        return $this->view->render($response, $this->template, $payload);
    }

    /**
     * Handle the request and return the payload.
     *
     * @return mixed[]
     */
    protected function handle(): array
    {
        // Create a dummy user to pre-populate fields
        $group = new $this->groupModel([
            'icon' => $this->defaultIcon,
        ]);

        // Load the request schema & validator
        $schema = $this->getSchema();

        // Determine form fields to hide/disable
        $fields = [
            'hidden'   => [],
            'disabled' => [],
        ];

        return [
            'group'   => $group,
            'form'    => [
                'action'      => 'api/groups',
                'method'      => 'POST',
                'fields'      => $fields,
                'submit_text' => $this->translator->translate('CREATE'),
            ],
            'page'    => [
                'validators' => $this->adapter->rules($schema),
            ],
        ];
    }

    /**
     * Validate access to the page.
     *
     * @throws ForbiddenException
     */
    protected function validateAccess(): void
    {
        if (!$this->authenticator->checkAccess('create_group')) {
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
}
