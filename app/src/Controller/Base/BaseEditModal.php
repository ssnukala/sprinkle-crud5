<?php

declare(strict_types=1);

/*
 * UserFrosting Admin Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-admin
 * @copyright Copyright (c) 2022 Alexander Weissman & Louis Charette
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
//use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\GroupInterface;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
use UserFrosting\Sprinkle\Core\I18n\SiteLocaleInterface;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;

use UserFrosting\Sprinkle\CRUD5\Database\Models\Interfaces\CRUD5ModelInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

/**
 * Renders the modal form for editing an existing group.
 *
 * This does NOT render a complete page.  Instead, it renders the HTML for the modal, which can be embedded in other pages.
 * This page requires authentication.
 *
 * Request type: GET
 */
class BaseEditModal
{
    /** @var string Page template */
    protected string $template = 'modals/crud5-edit.html.twig';

    // Request schema for client side form validation
    protected string $schema = 'schema://requests/group/edit-info.yaml';

    protected string $action = 'api/crud5/';

    protected string $crud_slug_attribute = 'crud_slug';
    protected string $crud_slug = 'to_be_set';

    protected string $query_field = 'id';


    /**
     * Inject dependencies.
     */
    public function __construct(
        protected Authenticator $authenticator,
        protected CRUD5ModelInterface $crudModel,
        protected SiteLocaleInterface $siteLocale,
        protected Translator $translator,
        protected Twig $view,
        protected JqueryValidationArrayAdapter $adapter,
        protected DebugLoggerInterface $debugLogger
    ) {
        //$this->debugLogger->debug("Line 58 - BaseEditModal constructor: Record - Table :  " . $crudModel->getTable(), $crudModel->toArray());
    }

    /**
     * Receive the request, dispatch to the handler, and return the payload to
     * the response.
     *
     * @param CRUD5ModelInterface $group    The group to edit, injected by the middleware.
     * @param Response       $response
     */
    public function __invoke(CRUD5ModelInterface $crudModel, Request $request, Response $response): Response
    {
        //$this->debugLogger->debug("Line 68 - BaseEditModal: Record - Table :  " . $crud5model->getTable(), $crud5model->toArray());
        $this->crud_slug = $this->getParameter($request, $this->crud_slug_attribute);
        $field = $this->query_field;
        $this->action = $this->action . $this->crud_slug . '/r/' . $crudModel->$field;

        $payload = $this->handle($crudModel);
        //$this->debugLogger->debug("Line 70 - BaseEditModal: Payload ", $payload);
        return $this->view->render($response, $this->template, $payload);
    }

    /**
     * Handle the request and return the payload.
     *
     * @param CRUD5ModelInterface $group
     *
     * @return mixed[]
     */
    protected function handle(CRUD5ModelInterface $crudModel): array
    {
        // Access-controlled resource - check that currentUser has permission
        // to edit basic fields "name", "slug", "icon", "description" for this group
        $fieldNames = ['name', 'slug', 'icon', 'description'];
        if (!$this->authenticator->checkAccess('update_group_field', [
            'group'  => $crudModel,
            'fields' => $fieldNames,
        ])) {
            throw new ForbiddenException();
        }

        // Generate form
        $fields = [
            'hidden'   => [],
            'disabled' => [],
        ];

        // Load validation rules
        $schema = $this->getSchema();

        return [
            'group'   => $crudModel,
            'form'    => [
                'crud5_action'      => $this->action,
                'action'      => "api/groups/g/{$crudModel->slug}",
                'method'      => 'PUT',
                'fields'      => $fields,
                'submit_text' => $this->translator->translate('UPDATE'),
            ],
            'page'    => [
                'validators' => $this->adapter->rules($schema),
            ],
        ];
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

    protected function getParameter(ServerRequestInterface $request, string $key): ?string
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        return $route?->getArgument($key) ?? $request->getQueryParams()[$key] ?? null;
    }
}
