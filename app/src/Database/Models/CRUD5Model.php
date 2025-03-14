<?php

declare(strict_types=1);

/*
 * UserFrosting Account Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-account
 * @copyright Copyright (c) 2022 Alexander Weissman & Louis Charette
 * @license   https://github.com/userfrosting/sprinkle-account/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD5\Database\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;
use UserFrosting\Sprinkle\CRUD5\Database\Factories\CRUD5ModelFactory;
use UserFrosting\Sprinkle\CRUD5\Database\Models\Interfaces\CRUD5ModelInterface;
use UserFrosting\Sprinkle\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\UserInterface;

/**
 * CRUD5Model Model.
 *
 * Represents a generic database table for CRUD.
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class CRUD5Model extends Model implements CRUD5ModelInterface
{
    use HasFactory;

    /**
     * @var string The name of the table for the current model.
     */
    protected $table = 'CRUD5_NOT_SET';

    /**
     * @var string[] The attributes that are mass assignable.
     */
    protected $fillable = [];

    /**
     * @var string[] The attributes that should be cast.
     */
    protected $casts = ['meta' => 'array'];

    /**
     * @var bool Disable timestamps for this class.
     */
    public $timestamps = true;

    /*
    public function __construct(
        $table = 'CRUD5_NOT_SET'
    ) {
        $this->table = $table;
        parent::__construct();
    }
*/

    /**
     * Set the the fillable Array
     *
     * @param array $fillable
     *
     * @return void
     */
    public function setFillable($fillable = [])
    {
        $this->fillable = $fillable;
    }

    public function getFillable()
    {
        return $this->fillable;
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return CRUD5ModelFactory::new();
    }
    /**
     * Lazily load a collection of Users which belong to this group.
     *
     * @return HasMany
     */
    public function users(): HasMany
    {
        /** @var string */
        $relation = static::$ci?->get(UserInterface::class);

        return $this->hasMany($relation, 'group_id');
    }
}
