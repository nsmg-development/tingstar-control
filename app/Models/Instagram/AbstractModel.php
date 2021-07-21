<?php

namespace App\Models\Instagram;

use App\Traits\ArrayLikeTrait;
use App\Traits\InitializerTrait;
use ArrayAccess;

/**
 * Class AbstractModel
 */
abstract class AbstractModel implements ArrayAccess
{
    use InitializerTrait, ArrayLikeTrait;

    /**
     * @var array
     */
    protected static array $initPropertiesMap = [];

    /**
     * @return array
     */
    public static function getColumns(): array
    {
        return array_keys(static::$initPropertiesMap);
    }
}
