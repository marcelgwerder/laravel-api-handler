<?php

namespace Marcelgwerder\ApiHandler\Parsers;

use Marcelgwerder\ApiHandler\ApiHandler;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Http\Request;

abstract class Parser implements Scope
{
    protected $handler;

    public function __construct(ApiHandler $handler)
    {
        $this->handler = $handler;
    }

    abstract public function parse(Request $request): void;
}
