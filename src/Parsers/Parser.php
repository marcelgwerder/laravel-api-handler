<?php

namespace Marcelgwerder\ApiHandler\Parsers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Scope;
use Marcelgwerder\ApiHandler\ApiHandler;

abstract class Parser implements Scope
{
    protected $handler;

    public function __construct(ApiHandler $handler)
    {
        $this->handler = $handler;
    }

    abstract public function parse(Request $request): ?array;
}
