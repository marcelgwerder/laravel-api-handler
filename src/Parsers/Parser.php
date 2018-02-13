<?php

namespace Marcelgwerder\ApiHandler\Parsers;

use Illuminate\Database\Eloquent\Scope;

abstract class Parser implements Scope
{
    protected $handler;

    public function __construct(ApiHandler $handler)
    {
        $this->handler = $handler;
    }

    abstract public function parse(Request $request): void;
}
