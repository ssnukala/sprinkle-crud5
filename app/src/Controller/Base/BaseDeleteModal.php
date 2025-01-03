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
use UserFrosting\Config\Config;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
//use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\GroupInterface;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
use UserFrosting\Sprinkle\Admin\Exceptions\GroupException;
use UserFrosting\Support\Message\UserMessage;
use UserFrosting\Sprinkle\CRUD5\Database\Models\Interfaces\CRUD5ModelInterface;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;

/**
 * Get deletion confirmation modal.
 */
class BaseDeleteModal
{
    /** @var string Page template */
    protected string $template = 'modals/confirm-delete-group.html.twig';

    /**
     * Inject dependencies.
     */
    public function __construct(
        protected Authenticator $authenticator,
        protected Config $config,
        protected Twig $view,
        protected DebugLoggerInterface $debugLogger,
    ) {}

    /**
     * Receive the request, dispatch to the handler, and return the payload to
     * the response.
     *
     * @param CRUD5ModelInterface $crudModel
     * @param Response       $response
     */
    public function __invoke(CRUD5ModelInterface $crudModel, Response $response): Response
    {
        $payload = $this->handle($crudModel);

        return $this->view->render($response, $this->template, $payload);
    }

    /**
     * Handle the request and return the payload.
     *
     * @param GroupInterface $group
     *
     * @return mixed[]
     */
    protected function handle(CRUD5ModelInterface $crudModel): array
    {
        // Access-controlled page based on the group.
        $this->validateAccess($crudModel);

        // Check if there are any users in this group
        // @phpstan-ignore-next-line False negative from Laravel
        if ($crudModel->users()->count() > 0) {
            $e = new GroupException();
            $message = new UserMessage('GROUP.NOT_EMPTY', $crudModel->toArray());
            $e->setDescription($message);

            throw $e;
        }

        return [
            'group' => $crudModel,
            'form'  => [
                'action' => "api/groups/g/{$crudModel->slug}",
            ],
        ];
    }

    /**
     * Validate access to the page.
     *
     * @throws ForbiddenException
     */
    protected function validateAccess(CRUD5ModelInterface $crudModel): void
    {
        if (!$this->authenticator->checkAccess('delete_group', ['group' => $crudModel])) {
            throw new ForbiddenException();
        }
    }
}
