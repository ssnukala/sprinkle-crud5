<?php

declare(strict_types=1);

/*
 * UserFrosting CRID5 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-crud5
 * @copyright Copyright (c) 2022 Srinivas Nukala
 * @license   https://github.com/userfrosting/sprinkle-admin/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD5\Sprunje;

use Illuminate\Database\Eloquent\Model;
use UserFrosting\Sprinkle\CRUD5\Database\Models\Interfaces\CRUD5ModelInterface;
use UserFrosting\Sprinkle\Core\Sprunje\Sprunje;

/**
 * Implements Sprunje for the groups API.
 */
class CRUD5Sprunje extends Sprunje
{
    protected string $name = 'TO_BE_SET';

    protected array $sortable = ["name"];

    protected array $filterable = [];

    public function __construct(
        protected CRUD5ModelInterface $model,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function baseQuery()
    {
        // @phpstan-ignore-next-line Model implement Model.
        return $this->model;
    }
}
