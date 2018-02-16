<?php

namespace Marcelgwerder\ApiHandler\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Marcelgwerder\ApiHandler\ApiHandler
 */
class ApiHandler extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'apihandler';
    }
}
