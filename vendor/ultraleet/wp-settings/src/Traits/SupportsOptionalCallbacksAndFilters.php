<?php

namespace Ultraleet\WP\Settings\Traits;

use Ultraleet\WP\Settings\Exceptions\InvalidTypeException;

trait SupportsOptionalCallbacksAndFilters
{

    /**
     * Filters a variable through a callable or WP filter if one exists. Otherwise returns it unchanged.
     *
     * Type checks are performed at every stage. In the end, the function will attempt to cast the value
     * to the provided type (currently only works for values supported by settype() and not class names).
     * In case of failure, throws an exception.
     *
     * @param $value
     * @param string $type
     * @param bool $strict
     * @return mixed|void
     *
     * @todo Move to a more global space so it can be reused in other projects.
     */
    protected static function filterIfCallbackOrFilter($value, $type = 'array', $strict = false)
    {
        $error = false;
        $varType = gettype($value);
        if (is_callable($value)) {
            $value = call_user_func($value);
            if (static::isCorrectType($value, $type)) {
                return $value;
            }
            $error = "Provided callable did not return the correct type ($type).";
        } elseif (is_string($value) && has_filter($value)) {
            $value = apply_filters($value, null);
            if (static::isCorrectType($value, $type)) {
                return $value;
            }
            $error = "Provided filter '$value' did not return the correct type ($type).";
        }
        if (($error || !static::isCorrectType($value, $type)) && ($strict || !settype($value, $type))) {
            $error = $error ?: "Provided value is not of and cannot be cast to the correct type ($type).";
        }
        if ($error) {
            throw new InvalidTypeException($error);
        }
        return $value;
    }

    private static function isCorrectType($variable, $type): bool
    {
        $resultType = gettype($variable);
        return $type == $resultType || ('object' == $resultType && static::isInstanceOf($variable, $type));
    }

    /**
     * Check whether or not we have an instance or descendant of a given class or interface.
     *
     * @param object $object
     * @param string $classOrInterface
     * @return bool
     */
    private static function isInstanceOf(object $object, string $classOrInterface): bool
    {
        if (is_a($object, $classOrInterface)) {
            return true;
        }
        return is_subclass_of($object, $classOrInterface, false);
    }
}
