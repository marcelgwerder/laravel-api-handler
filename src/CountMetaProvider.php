<?php namespace Marcelgwerder\ApiHandler;

class CountMetaProvider extends MetaProvider
{
    /**
     * Query builder object
     *
     * @var mixed
     */
    protected $builder;

    public function __construct($title, $builder)
    {
        $this->builder = clone $builder;
        $this->title = $title;

        //Remove offset from builder because a count doesn't work in combination with an offset
        $this->builder->offset = null;

        //Remove orders from builder because they are irrelevant for counts and can cause errors with renamed columns
        $this->builder->orders = null;
    }

    /**
     * Get the meta information
     *
     * @return string
     */
    public function get()
    {
        if (!empty($this->builder->groups)) {
            //Only a count column is required
            $this->builder->columns = [];
            $this->builder->selectRaw('count(*) as aggregate');
            $this->builder->limit = null;

            //Use the original builder as a subquery and count over it because counts over groups return the number of rows for each group, not for the total results
            $query = $this->builder->newQuery()->selectRaw('count(*) as aggregate from (' . $this->builder->toSql() . ') as count_table', $this->builder->getBindings());
            return intval($query->first()->aggregate);
        }

        return intval($this->builder->count());
    }
}
