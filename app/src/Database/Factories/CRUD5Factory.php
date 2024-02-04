<?php

declare(strict_types=1);

/*
 * UserFrosting Account Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-account
 * @copyright Copyright (c) 2022 Alexander Weissman & Louis Charette
 * @license   https://github.com/userfrosting/sprinkle-account/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD5\Database\Factories;

use DateTime;
use UserFrosting\Sprinkle\CRUD5\Database\Models\CRUD5Model;
use UserFrosting\Sprinkle\Core\Database\Factories\Factory;

class CRUD5ModelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = CRUD5Model::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'ip_address'  => $this->faker->ipv4(),
            'type'        => $this->faker->word(),
            'occurred_at' => new DateTime('now'),
            'description' => $this->faker->sentence(),
        ];
    }
}
