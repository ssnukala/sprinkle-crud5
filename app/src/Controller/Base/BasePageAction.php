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
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\GroupInterface;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
use UserFrosting\Sprinkle\Core\I18n\SiteLocaleInterface;
use UserFrosting\Sprinkle\Core\Log\DebugLogger;
use UserFrosting\Sprinkle\CRUD5\Database\Models\Interfaces\CRUD5ModelInterface;

/**
 * Renders a page displaying a group's information, in read-only mode.
 *
 * This checks that the currently logged-in user has permission to view the requested group's info.
 * It checks each field individually, showing only those that you have permission to view.
 * This will also try to show buttons for deleting, and editing the group.
 * This page requires authentication.
 *
 * Request type: GET
 */
class BasePageAction
{
    /** @var string Page template */
    protected string $template = 'pages/group.html.twig';

    /**
     * Inject dependencies.
     */
    public function __construct(
        protected Authenticator $authenticator,
        protected DebugLogger $logger,
        protected SiteLocaleInterface $siteLocale,
        protected RouteParserInterface $routeParser,
        protected Twig $view,
    ) {
    }

    /**
     * Receive the request, dispatch to the handler, and return the payload to
     * the response.
     *
     * @param GroupInterface $group    The group to display, injected by the middleware.
     * @param Response       $response
     */
    public function __invoke(CRUD5ModelInterface $crud5, string $crmodel, Request $request, Response $response): Response
    {
        $this->logger->debug("Line 60:BasePageAction  Slug is $crmodel");
        $crud5->setTable($crmodel);
        $payload = $this->handle($crud5);
        return $this->view->render($response, $this->template, $payload);
    }


    /**
     * Handle the request and return the payload.
     *
     * @param GroupInterface $group
     *
     * @return mixed[]
     */
    protected function handle(CRUD5ModelInterface $crud5): array
    {
        // Access-controlled page
        if (!$this->authenticator->checkAccess('uri_group', [
            'group' => $crud5,
        ])) {
            throw new ForbiddenException();
        }

        // Determine fields that currentUser is authorized to view
        $fieldNames = ['name', 'slug', 'icon', 'description'];

        // Fields to hide based on user's access.
        $fields = [
            'hidden' => [],
        ];

        // Determine which fields should be hidden
        foreach ($fieldNames as $field) {
            if (!$this->authenticator->checkAccess('view_group_field', [
                'group'    => $crud5,
                'property' => $field,
            ])) {
                $fields['hidden'][] = $field;
            }
        }

        // Determine buttons to display
        $editButtons = [
            'hidden' => [],
        ];

        if (!$this->authenticator->checkAccess('update_group_field', [
            'group'  => $crud5,
            'fields' => ['name', 'slug', 'icon', 'description'],
        ])) {
            $editButtons['hidden'][] = 'edit';
        }

        if (!$this->authenticator->checkAccess('delete_group', [
            'group' => $crud5,
        ])) {
            $editButtons['hidden'][] = 'delete';
        }

        return [
            'group'           => $crud5,
            'fields'          => $fields,
            'tools'           => $editButtons,
            'delete_redirect' => $this->routeParser->urlFor('uri_groups'),
        ];
    }
}
