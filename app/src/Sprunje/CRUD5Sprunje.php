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
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;

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
        protected Request $request
    ) {
        //$routeContext = RouteContext::fromRequest($request);
        //$route = $routeContext->getRoute();
        //$crudSlug = $route?->getArgument('crud_slug'); // Extract the slug
        error_log("Line 34: CRUD5Sprunje: " . $model->getTable());
        //$model->setTable($crudSlug);
        //$this->model = $model;
        parent::__construct();
    }


    public function setupSprunje($name, $sortable = [], $filterable = []): void
    {
        error_log("Line 39: CRUD5 Sprunje: " . $name . " Model table is " . $this->model->getTable());
        $this->model->setTable($name);
        error_log("Line 41: CRUD5 Sprunje: " . $name . " Model table is " . $this->model->getTable());
        $this->name = $name;
        $this->sortable = $sortable;
        $this->filterable = $filterable;

        $query = $this->baseQuery();
        if (is_a($query, Model::class)) {
            $query = $query->newQuery();
        }

        $this->query = $query;
    }

    /**
     * {@inheritdoc}
     */
    protected function baseQuery()
    {
        // @phpstan-ignore-next-line Model implement Model.
        error_log("Line 53: CRUD5 Sprunje:  Model table is " . $this->model->getTable());
        return $this->model;
    }
}