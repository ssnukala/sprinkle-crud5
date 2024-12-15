<?php

declare(strict_types=1);

/*
 * UserFrosting Admin Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-crud5
 * @copyright Copyright (c) 2022 Srinivas Nukala
 * @license   https://github.com/userfrosting/sprinkle-crud5/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD5\Controller\Base;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
//use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\GroupInterface;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
//use UserFrosting\Sprinkle\Admin\Sprunje\UserSprunje;

use UserFrosting\Sprinkle\CRUD5\Database\Models\Interfaces\CRUD5ModelInterface;

use UserFrosting\Sprinkle\CRUD5\Sprunje\CRUD5Sprunje;

/**
 * CRUDModel List API.
 */
class BaseSprunje
{
    /**
     * Inject dependencies.
     */
    public function __construct(
        protected Authenticator $authenticator,
        protected CRUD5Sprunje $sprunje,
    ) {
    }

    /**
     * Receive the request, dispatch to the handler, and return the payload to
     * the response.
     *
     * @param CRUD5ModelInterface $group    The group to get the users for, injected by the Middleware.
     * @param Request        $request
     * @param Response       $response
     */
    public function __invoke(CRUD5ModelInterface $crudmodel, Request $request, Response $response): Response
    {
        $this->validateAccess($crudmodel);

        // GET parameters and pass to Sprunje
        $params = $request->getQueryParams();
        $this->sprunje->setOptions($params);

        // Be careful how you consume this data - it has not been escaped and contains untrusted user-supplied content.
        // For example, if you plan to insert it into an HTML DOM, you must escape it on the client side (or use client-side templating).
        return $this->sprunje->toResponse($response);
    }

    /**
     * Validate access to the page.
     *
     * @param CRUD5ModelInterface $group
     *
     * @throws ForbiddenException
     */
    protected function validateAccess(CRUD5ModelInterface $group): void
    {
        if (!$this->authenticator->checkAccess('view_group_field', [
            'group'    => $group,
            'property' => 'users',
        ])) {
            throw new ForbiddenException();
        }
    }
}
