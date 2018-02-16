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
            $strawExp = '/^' . str_replace('\*', '.*', preg_quote($straw)) . '$/';

            if (preg_match($strawExp, $path)) {
                return true;
            }
        }

        return false;
    }
}
