<?php

namespace Marcelgwerder\ApiHandler\Parsers;

use Marcelgwerder\ApiHandler\Exceptions\InvalidSortException;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ExpansionParser extends Parser
{
    protected $sorts = [];

    public function parse(Request $request): void
    {
        $this->handler->getParser(ExpansionParser::class);
       
    }

    public function apply(Builder $builder, Model $model)
    {
        
    }
}
