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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Renders the group listing page.
 *
 * This page renders a table of groups, with dropdown menus for admin actions for each group.
 * Actions typically include: edit group, delete group.
 * This page requires authentication.
 *
 * Request type: GET
 */
class BaseAction
{

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
     * Sprunje / api handler tied to this page.
     *
     * @param Request  $request
     * @param Response $response
     */

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

    protected function getParameter(ServerRequestInterface $request, string $key): ?string
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        return $route?->getArgument($key) ?? $request->getQueryParams()[$key] ?? null;
    }
}
