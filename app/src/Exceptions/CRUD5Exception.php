<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD5 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-account
 * @copyright Copyright (c) 2022 Alexander Weissman & Louis Charette
 * @license   https://github.com/userfrosting/sprinkle-account/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD5\Exceptions;

use Exception;
use UserFrosting\Sprinkle\Core\Exceptions\UserFacingException;
use UserFrosting\Support\Message\UserMessage;

/**
 * Base exception for Auth related Exception.
 *
 * This exception is used as umbrella exception for all Account related
 * exception to make it easier to catch them.
 */
class CRUD5Exception extends UserFacingException
{
    protected string $title = 'ACCOUNT.EXCEPTION.TITLE';
    protected string|UserMessage $description = 'ACCOUNT.EXCEPTION.DESCRIPTION';
}