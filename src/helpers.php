<?php

namespace Marcelgwerder\ApiHandler\helpers {
    /**
     * Return the given value, optionally passed through the given callback.
     *
     * @param  mixed  $value
     * @param  callable|null  $callback
     * @return mixed
     */
    function is_allowed_path($path, array $haystack)
    {
        foreach ($haystack as $straw) {
            $strawExp = '/^'.str_replace('\*', '.*', preg_quote($straw)).'$/';

            if (preg_match($strawExp, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a callable returns a specific type.
     */
    function returns_type(string $className, string $methodName, string $type): boolean
    {
        $method = new ReflectionMethod($className, $methodName);

        return $method->getReturnType() === $type;
    }

    function array_undot(string $dotPath)
    {
        $array = [];

        foreach ($dotNotationArray as $key => $value) {
            array_set($array, $key, $value);
        }

        return $array;
    }

    function nullify_empty($var)
    {
        if (empty($var)) {
            return null;
        }

        return $var;
    }

    function unqualify_column($column)
    {
        $pos = (strpos($column, '.') ?: 0) + 1;

        return substr($column, $pos === 1 ? 0 : $pos);
    }
}
