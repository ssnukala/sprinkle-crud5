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
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
//use UserFrosting\Sprinkle\Admin\Sprunje\GroupSprunje;
use UserFrosting\Sprinkle\CRUD5\Sprunje\CRUD5Sprunje;
use Slim\Routing\RouteContext;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Support\Repository\Loader\YamlFileLoader;

/**
 * Renders the group listing page.
 *
 * This page renders a table of groups, with dropdown menus for admin actions for each group.
 * Actions typically include: edit group, delete group.
 * This page requires authentication.
 *
 * Request type: GET
 */
class BasePageListAction
{
    /** @var string Page template */
    protected string $template = 'pages/tobeset.html.twig';
    protected array $config;
    protected string $configFile = '';

    /**
     * Inject dependencies.
     * SN ToDo: Need to replace GroupSprunje with BaseSprunje and then dynanically instantiate the sprunje based on the value in crud_slug.
     */
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected CRUD5Sprunje $sprunje,
        protected Twig $view,
        protected DebugLoggerInterface $debugLogger,
    ) {
        //$this->logger->debug("Line 51: BasePageListAction: ");
    }

    protected function loadConfig($slug): void
    {
        $this->configFile = $configFile ?? "schema://crud5/$slug.yaml";
        $loader = new YamlFileLoader($this->configFile);
        $this->config = $loader->load(false);
    }

    protected function getSortable(): array
    {
        $sortable = [];
        foreach ($this->config['table']['columns'] as $name => $column) {
            if ($column['sortable']) {
                $sortable[] = $name;
            }
        }
        return $sortable;
    }
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

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $slug = $route?->getArgument('crud_slug');
        $this->debugLogger->debug("Line 66: BasePageListAction: " . $slug);
        $this->loadConfig($slug);
        /**
         * Loads the config into the class property.
         *
         * @throws \UserFrosting\Support\Exception\FileNotFoundException if config file not found
         */
        //$this->template = 'pages/' . $slug . '.html.twig';
        $this->template = 'pages/crudlist.html.twig';
        $page_data = ["crud5" => $this->config];
        return $this->view->render($response, $this->template, $page_data);
    }

    /**
     * Sprunje / api handler tied to this page.
     *
     * @param Request  $request
     * @param Response $response
     */
    public function sprunje(Request $request, Response $response): Response
    {
        $this->validateAccess();

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $slug = $route?->getArgument('crud_slug');
        $this->loadConfig($slug);

        // GET parameters and pass to Sprunje
        $params = $request->getQueryParams();
        $sortable = $this->getSortable();
        error_log("Line 110: CRUD5 Sprunje: " . $slug);
        //$this->logger->debug("Line 110: CRUD5 Sprunje: " . $slug);

        $this->sprunje->setupSprunje($slug, $sortable, $sortable);
        $this->sprunje->setOptions($params);

        // Be careful how you consume this data - it has not been escaped and contains untrusted user-supplied content.
        // For example, if you plan to insert it into an HTML DOM, you must escape it on the client side (or use client-side templating).
        return $this->sprunje->toResponse($response);
    }

    /**
     * Validate access to the page.
     *
     * @throws ForbiddenException
     */
    protected function validateAccess(): void
    {
        if (!$this->authenticator->checkAccess('uri_groups')) {
            throw new ForbiddenException();
        }
    }
}
