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
use Slim\Routing\RouteContext;
use UserFrosting\Sprinkle\FormGenerator\Form;
use UserFrosting\Support\Exception\FileNotFoundException;
use UserFrosting\Support\Repository\Loader\YamlFileLoader;

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
    //protected string $template = 'modals/crud5-edit.html.twig';

    // Request schema for client side form validation
    protected string $schema = 'schema://requests/group/edit-info.yaml';

    protected string $action = '/api/crud5/';

    protected string $crud_slug_attribute = 'crud_slug';
    protected string $crud_slug = 'to_be_set';

    protected string $crud_action = 'to_be_set';
    protected string $box_id = 'box-generic-id';

    protected string $query_field = 'id';
    protected array $config;
    protected string $configFile = '';
    protected string $user_permission = 'tobeset';


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
    public function __invoke(?CRUD5ModelInterface $crudModel, Request $request, Response $response): Response
    {
        //$this->debugLogger->debug("Line 68 - BaseEditModal: Record - Table :  " . $crud5model->getTable(), $crud5model->toArray());
        $this->crud_slug = $this->getParameter($request, $this->crud_slug_attribute);
        $this->crud_action = $this->getParameter($request, 'crud_action');
        $this->box_id = $this->getParameter($request, 'box_id');
        //$this->crud_action = 'create';
        $this->loadConfig($this->crud_slug);

        $file = $this->crud_action == 'create' ? '/create.yaml' : '/edit-info.yaml';
        $this->schema = 'schema://requests/' . $this->crud_slug . $file;
        $this->debugLogger->debug("Line 85 - BaseEditModal: CRUD Action " . date('mmddyyyy-hhmiss') . $this->crud_action);
        $field = $this->query_field;
        $this->action = $this->action . $this->crud_slug;
        if ($this->crud_action !== 'create') {
            $this->action .= "/r/" . $crudModel->$field;
        }
        $payload = $this->handle($crudModel);
        $this->debugLogger->debug("Line 70 - BaseEditModal: Payload ", $payload);
        //return $this->view->render($response, $this->template, $payload);

        return $this->view->render($response, "FormGenerator/modal.html.twig", $payload);
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
        // Load validation rules
        $schema = $this->getSchema();
        $form = new Form($schema, $crudModel->toArray());
        $form_fields = $form->generate();
        // Access-controlled resource - check that currentUser has permission
        // to edit basic fields "name", "slug", "icon", "description" for this group
        $field_names = array_keys($form_fields);
        if (!$this->authenticator->checkAccess($this->user_permission)) {
            throw new ForbiddenException();
        }

        return [
            "box_id"        => $this->box_id,
            "box_title"     => strtoupper($this->crud_slug) . ".UPDATE",
            "submit_button" => "SAVE",
            'form_action'      => $this->action,
            'form_method' => $this->crud_action === 'create' ? 'POST' : 'PUT',
            "fields"        => $form_fields,
            "validators"    => $this->adapter->rules($schema)
        ];
    }

    /**
     * Load the request schema.
     *
     * @return RequestSchemaInterface
     */
    protected function getSchema(): RequestSchemaInterface
    {
        try {
            $schema = new RequestSchema($this->schema);
        } catch (FileNotFoundException $e) {
            $this->debugLogger->error("Line 150 - BaseEditModal: Schema file not found: " . $this->schema);
            $this->schema = 'schema://requests/' . rtrim($this->crud_slug, 's') . '/edit-info.yaml';
            $this->debugLogger->error("Line 152 - BaseEditModal: trying new schema file: " . $this->schema);
            $schema = new RequestSchema($this->schema);
        }
        return $schema;
    }

    protected function getParameter(Request $request, string $key): ?string
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        return $route?->getArgument($key) ?? $request->getQueryParams()[$key] ?? null;
    }

    protected function loadConfig($slug): void
    {
        $this->configFile = $configFile ?? "schema://crud5/$slug.yaml";
        $loader = new YamlFileLoader($this->configFile);
        $this->config = $loader->load(false);
        $this->user_permission = $this->config['permission'];
        //$this->debugLogger->debug("Line 65: CRUD5 Sprunje: $slug - Configuration ", $this->config);
    }
}