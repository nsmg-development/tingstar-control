<?php

namespace App\Enums;

use ReflectionClass;

abstract class BaseEnum {
    private static $constCacheArray = null;

    /**
     * @throws \ReflectionException
     */
    private static function getConstants() {
        if (self::$constCacheArray == null) {
            self::$constCacheArray = [];
        }
        $calledClass = get_called_class();
        if (!array_key_exists($calledClass, self::$constCacheArray)) {
            $reflect = new ReflectionClass($calledClass);
            self::$constCacheArray[$calledClass] = $reflect->getConstants();
        }
        return self::$constCacheArray[$calledClass];
    }

    /**
     * @param $name
     * @param false $strict
     * @return bool
     * @throws \ReflectionException
     */
    public static function isValidName($name, bool $strict = false): bool
    {
        $constants = self::getConstants();

        if ($strict) {
            return array_key_exists($name, $constants);
        }

        $keys = array_map('strtolower', array_keys($constants));
        return in_array(strtolower($name), $keys);
    }

    /**
     * @param $value
     * @param bool $strict
     * @return bool
     * @throws \ReflectionException
     */
    public static function isValidValue($value, bool $strict = true): bool
    {
        $values = array_values(self::getConstants());
        return in_array($value, $values, $strict);
    }

    /**
     * @param $name
     * @param bool $strict
     * @return string

     */
    public static function getValueByName($name, bool $strict = false): string
    {
        if (self::isValidName($name, $strict)) {
            return strtolower($name);
        }

        return '';
    }
}
