<?php

/*
 * UserFrosting Admin Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-admin
 * @copyright Copyright (c) 2022 Alexander Weissman & Louis Charette
 * @license   https://github.com/userfrosting/sprinkle-admin/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD5\Tests;

use UserFrosting\Develop\Admin\App;
use UserFrosting\Testing\TestCase;

/**
 * Test case with Admin as main sprinkle
 */
class CRUD5TestCase extends TestCase
{
    protected string $mainSprinkle = App::class;
}
