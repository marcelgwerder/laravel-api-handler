<?php namespace Marcelgwerder\ApiHandler;

use \ArrayObject;
use \Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use \Illuminate\Database\Eloquent\Relations\BelongsTo;
use \Illuminate\Database\Eloquent\Relations\BelongsToMany;
use \Illuminate\Database\Eloquent\Relations\HasMany;
use \Illuminate\Database\Eloquent\Relations\HasManyThrough;
use \Illuminate\Database\Eloquent\Relations\HasOne;
use \Illuminate\Database\Eloquent\Relations\MorphMany;
use \Illuminate\Database\Eloquent\Relations\MorphOne;
use \Illuminate\Database\Query\Builder as QueryBuilder;
use \Illuminate\Support\Facades\Config;
use \InvalidArgumentException;
use \ReflectionObject;

class Parser
{
    /**
     * The builder Instance.
     *
     * @var mixed
     */
    public $builder;

    /**
     * The original builder Instance.
     *
     * @var mixed
     */
    public $originalBuilder;

    /**
     * The parsed meta information
     *
     * @var array
     */
    public $meta = [];

    /**
     * If the parser works on multiple datasets
     *
     * @var boolean
     */
    public $multiple;

    /**
     * The mode for the response (count,default)
     *
     * @var string
     */
    public $mode = 'default';

    /**
     * If the response should be in an envelope or not
     *
     * @var boolean
     */
    public $envelope;

    /**
     * The base query builder instance.
     *
     * @var \Illuminate\Database\Query\Builder
     */
    protected $query;

    /**
     * The original query builder instance.
     *
     * @var \Illuminate\Database\Query\Builder
     */
    protected $originalQuery;

    /**
     * The http query params.
     *
     * @var array
     */
    protected $params;

    /**
     * Predefined functions
     *
     * @var array
     */
    protected $functions = ['fields', 'sort', 'limit', 'offset', 'config', 'with', 'q'];

    /**
     * All functional params
     */
    protected $functionalParams;

    /**
     * All fields that belong to a relation
     *
     * @var array
     */
    protected $additionalFields = [];

    /**
     * All sorts that belong to a relation
     *
     * @var array
     */
    protected $additionalSorts = [];

    /**
     * If builder is an eloquent builder or not
     *
     * @var boolean
     */
    protected $isEloquentBuilder = false;

    /**
     * If builder is an query builder or not
     *
     * @var boolean
     */
    protected $isQueryBuilder = false;

    /**
     * Instantiate the Parser class
     *
     * @param mixed $builder
     * @param array $params
     */
    public function __construct($builder, $params)
    {
        $this->builder = $builder;
        $this->params = $params;

        $this->prefix = Config::get('apihandler.prefix');
        $this->envelope = Config::get('apihandler.envelope');

        $isEloquentModel = is_subclass_of($builder, '\Illuminate\Database\Eloquent\Model');
        $isEloquentRelation = is_subclass_of($builder, '\Illuminate\Database\Eloquent\Relations\Relation');

        $this->isEloquentBuilder = $builder instanceof EloquentBuilder;
        $this->isQueryBuilder = $builder instanceof QueryBuilder;

        if ($this->isEloquentBuilder) {
            $this->query = $builder->getQuery();
        } else if ($isEloquentRelation) {
            $this->builder = $builder->getQuery();
            $this->query = $builder->getBaseQuery();
            $this->isEloquentBuilder = true;
        } else if ($isEloquentModel) {
            //Convert the model to a builder object
            $this->builder = $builder->newQuery();

            $this->query = $this->builder->getQuery();

            $this->isEloquentBuilder = true;
        } else if ($this->isQueryBuilder) {
            $this->query = $builder;
        } else {
            throw new InvalidArgumentException('The builder argument has to the wrong type.');
        }

        $this->originalBuilder = clone $this->builder;
        $this->originalQuery = clone $this->query;
    }

    /**
     * Parse the query parameters with the given options.
     * Either for a single dataset or multiple.
     *
     * @param  mixed    $options
     * @param  boolean  $multiple
     * @return void
     */
    public function parse($options, $multiple = false)
    {
        $this->multiple = $multiple;

        if ($multiple) {
            $fullTextSearchColumns = $options;

            //Parse and apply offset using the laravel "offset" function
            if ($offset = $this->getParam('offset')) {
                $offset = intval($offset);
                $this->query->offset($offset);
            }

            //Parse and apply limit using the laravel "limit" function
            if ($limit = $this->getParam('limit')) {
                $limit = intval($limit);
                $this->query->limit($limit);
            }

            //Parse and apply the filters using the different laravel "where" functions
            //Every parameter that has not a predefined functionality gets parsed as a filter
            if ($filterParams = $this->getFilterParams()) {
                $this->parseFilter($filterParams);
            }

            //Parse an apply the fulltext search using the different laravel "where" functions
            //The fulltext search is only applied to the columns passed by $fullTextSearchColumns.
            if ($this->getParam('q') !== false) {
                $this->parseFulltextSearch($this->getParam('q'), $fullTextSearchColumns);
            }
        } else {
            $identification = $options;

            if (is_numeric($identification)) {
                $this->query->where('id', $identification);
            } else if (is_array($identification)) {
                foreach ($identification as $column => $value) {
                    $this->query->where($column, $value);
                }
            }
        }

        //Parse and apply field elements using the laravel "select" function
        //The needed fields for the with function (Primary and foreign keys) have to be added accordingly
        if ($fields = $this->getParam('fields')) {
            $this->parseFields($fields);
        }

        //Parse and apply sort elements using the laravel "orderBy" function
        if ($sort = $this->getParam('sort')) {
            $this->parseSort($sort);
        }

        //Parse and apply with elements using the Laravel "with" function
        if (($with = $this->getParam('with')) && $this->isEloquentBuilder) {
            $this->parseWith($with);
        }

        //Parse the config params
        if ($config = $this->getParam('config')) {
            $this->parseConfig($config);
        }

        if ($this->isEloquentBuilder) {
            //Attach the query builder object back to the eloquent builder object
            $this->builder->setQuery($this->query);
        }
    }

    /**
     * Set the config object
     *
     * @param mixed $config
     */
    public function setConfigHandler($config)
    {
        $this->config = $config;
    }

    /**
     * Get a parameter
     *
     * @param  string         $param
     * @return string|boolean
     */
    protected function getParam($param)
    {
        if (isset($this->params[$this->prefix . $param])) {
            return $this->params[$this->prefix . $param];
        }

        return false;
    }

    /**
     * Get the relevant filter parameters
     *
     * @return array|boolean
     */
    protected function getFilterParams()
    {
        $reserved = array_fill_keys($this->functions, true);
        $prefix = $this->prefix;

        $filterParams = array_diff_ukey($this->params, $reserved, function ($a, $b) use ($prefix) {
            return $a != $prefix . $b;
        });

        if (count($filterParams) > 0) {
            return $filterParams;
        }

        return false;
    }

    /**
     * Parse the fields parameter and return an array of fields
     *
     * @param  string     $fieldsParam
     * @return void
     */
    protected function parseFields($fieldsParam)
    {
        $fields = [];

        foreach (explode(',', $fieldsParam) as $field) {
            //Only add the fields that are on the base resource
            if (strpos($field, '.') === false) {
                $fields[] = trim($field);
            } else {
                $this->additionalFields[] = trim($field);
            }
        }

        if (count($fields) > 0) {
            $this->query->addSelect($fields);
        }

        if (is_array($this->query->columns)) {
            $this->query->columns = array_diff($this->query->columns, ['*']);
        }
    }

    /**
     * Parse the with parameter
     *
     * @param  string $withParam
     * @return void
     */
    protected function parseWith($withParam)
    {
        $fields = $this->query->columns;
        $fieldsCount = count($fields);
        $baseModel = $this->builder->getModel();

        $withHistory = [];

        foreach (explode(',', $withParam) as $with) {
            //Use ArrayObject to be able to copy the array (for array_splice)
            $parts = new ArrayObject(explode('.', $with));
            $lastKey = count($parts) - 1;

            for ($i = 0; $i <= $lastKey; $i++) {
                $part = $parts[$i];
                $partsCopy = $parts->getArrayCopy();

                //Get the previous history path (e.g. if current is a.b.c the previous is a.b)
                $previousHistoryPath = implode('.', array_splice($partsCopy, 0, $i));

                //Get the current history part based on the previous one
                $currentHistoryPath = $previousHistoryPath ? $previousHistoryPath . '.' . $part : $part;

                //Create new history element
                if (!isset($withHistory[$currentHistoryPath])) {
                    $withHistory[$currentHistoryPath] = ['fields' => []];
                }

                //Get all given fields related to the current part
                $withHistory[$currentHistoryPath]['fields'] = array_filter($this->additionalFields, function ($field) use ($part) {
                    return preg_match('/' . $part . '\..+$/', $field);
                });

                //Get all given sorts related to the current part
                $withHistory[$currentHistoryPath]['sorts'] = array_filter($this->additionalSorts, function ($pair) use ($part) {
                    return preg_match('/' . $part . '\..+$/', $pair[0]);
                });

                if (!isset($previousModel)) {
                    $previousModel = $baseModel;
                }

                //Throw a new ApiHandlerException if the relation doesn't exist
                //or is not properly marked as a relation
                if (!$this->isRelation($previousModel, $part)) {
                    throw new ApiHandlerException('UnknownResourceRelation', ['relation' => $part]);
                }

                $relation = call_user_func([$previousModel, $part]);
                $relationType = $this->getRelationType($relation);

                if ($relationType === 'BelongsTo') {
                    $firstKey = $relation->getQualifiedForeignKey();
                    $secondKey = $relation->getQualifiedParentKeyName();
                } else if ($relationType === 'HasMany' || $relationType === 'HasOne') {
                    $firstKey = $relation->getQualifiedParentKeyName();
                    $secondKey = $relation->getForeignKey();
                } else if ($relationType === 'BelongsToMany') {
                    $firstKey = $relation->getQualifiedParentKeyName();
                    $secondKey = $relation->getRelated()->getQualifiedKeyName();
                } else if ($relationType === 'HasManyThrough') {
                    $firstKey = $relation->getHasCompareKey();
                    $secondKey = null;
                } else {
                    die('Relation type not supported!');
                }

                //Check if we're on level 1 (e.g. a and not a.b)
                if ($firstKey !== null && $previousHistoryPath == '') {
                    if ($fieldsCount > 0 && !in_array($firstKey, $fields)) {
                        $fields[] = $firstKey;
                    }
                } else if ($firstKey !== null) {
                    if (count($withHistory[$previousHistoryPath]['fields']) > 0 && !in_array($firstKey, $withHistory[$previousHistoryPath]['fields'])) {
                        $withHistory[$previousHistoryPath]['fields'][] = $firstKey;
                    }
                }

                if ($secondKey !== null && count($withHistory[$currentHistoryPath]['fields']) > 0 && !in_array($secondKey, $withHistory[$currentHistoryPath]['fields'])) {
                    $withHistory[$currentHistoryPath]['fields'][] = $secondKey;
                }

                $previousModel = $relation->getModel();
            }

            unset($previousModel);
        }

        //Apply the withHistory to using the laravel "with" function
        $withsArr = [];

        foreach ($withHistory as $withHistoryKey => $withHistoryValue) {
            $withsArr[$withHistoryKey] = function ($query) use ($withHistory, $withHistoryKey) {

                //Reduce field values to fieldname
                $fields = array_map(function ($field) {
                    $pos = strpos($field, '.');
                    return $pos !== false ? substr($field, $pos + 1) : $field;
                }, $withHistory[$withHistoryKey]['fields']);

                if (count($fields) > 0 && is_array($fields)) {
                    $query->select($fields);
                }

                //Attach sorts
                foreach ($withHistory[$withHistoryKey]['sorts'] as $pair) {
                    $pos = strpos($pair[0], '.');
                    $pair = $pos !== false ? [substr($pair[0], $pos + 1), $pair[1]] : $pair;

                    call_user_func_array([$query, 'orderBy'], $pair);
                }
            };
        }

        $this->builder->with($withsArr);

        //Merge the base fields
        if (count($fields) > 0) {
            if (!is_array($this->query->columns)) {
                $this->query->columns = [];
            }

            $this->query->columns = array_merge($this->query->columns, $fields);
        }
    }

    /**
     * Parse the sort param and determine whether the sorting is ascending or descending.
     * A descending sort has a leading "-". Apply it to the query.
     *
     * @param  string $sortParam
     * @return void
     */
    protected function parseSort($sortParam)
    {
        foreach (explode(',', $sortParam) as $sortElem) {
            //Check if ascending or derscenting(-) sort
            if (preg_match('/^-.+/', $sortElem)) {
                $direction = 'desc';
            } else {
                $direction = 'asc';
            }

            $pair = [preg_replace('/^-/', '', $sortElem), $direction];

            //Only add the sorts that are on the base resource
            if (strpos($sortElem, '.') === false) {
                call_user_func_array([$this->query, 'orderBy'], $pair);
            } else {
                $this->additionalSorts[] = $pair;
            }
        }
    }

    /**
     * Parse the remaining filter params
     *
     * @param  array $filterParams
     * @return void
     */
    protected function parseFilter($filterParams)
    {
        $supportedPostfixes = [
            'st' => '<',
            'gt' => '>',
            'min' => '>=',
            'max' => '<=',
            'lk' => 'LIKE',
            'not-lk' => 'NOT LIKE',
            'in' => 'IN',
            'not-in' => 'NOT IN',
            'not' => '!=',
        ];

        $supportedPrefixesStr = implode('|', $supportedPostfixes);
        $supportedPostfixesStr = implode('|', array_keys($supportedPostfixes));

        foreach ($filterParams as $filterParamKey => $filterParamValue) {
            $keyMatches = [];

            //Matches every parameter with an optional prefix and/or postfix
            //e.g. not-title-lk, title-lk, not-title, title
            $keyRegex = '/^(?:(' . $supportedPrefixesStr . ')-)?(.*?)(?:-(' . $supportedPostfixesStr . ')|$)/';

            preg_match($keyRegex, $filterParamKey, $keyMatches);

            if (!isset($keyMatches[3])) {
                if (strtolower(trim($filterParamValue)) == 'null') {
                    $comparator = 'NULL';
                } else {
                    $comparator = '=';
                }
            } else {
                if (strtolower(trim($filterParamValue)) == 'null') {
                    $comparator = 'NOT NULL';
                } else {
                    $comparator = $supportedPostfixes[$keyMatches[3]];
                }
            }

            $column = $keyMatches[2];

            if ($comparator == 'IN') {
                $values = explode(',', $filterParamValue);

                $this->query->whereIn($column, $values);
            } else if ($comparator == 'NOT IN') {
                $values = explode(',', $filterParamValue);

                $this->query->whereNotIn($column, $values);
            } else {
                $values = explode('|', $filterParamValue);

                if (count($values) > 1) {
                    $this->query->where(function ($query) use ($column, $comparator, $values) {
                        foreach ($values as $value) {
                            if ($comparator == 'LIKE' || $comparator == 'NOT LIKE') {
                                $value = preg_replace('/(^\*|\*$)/', '%', $value);
                            }

                            //Link the filters with AND of there is a "not" and with OR if there's none
                            if ($comparator == '!=' || $comparator == 'NOT LIKE') {
                                $query->where($column, $comparator, $value);
                            } else {
                                $query->orWhere($column, $comparator, $value);
                            }
                        }
                    });
                } else {
                    $value = $values[0];

                    if ($comparator == 'LIKE' || $comparator == 'NOT LIKE') {
                        $value = preg_replace('/(^\*|\*$)/', '%', $value);
                    }

                    if ($comparator == 'NULL' || $comparator == 'NOT NULL') {
                        $this->query->whereNull($column, 'and', $comparator == 'NOT NULL');
                    } else {
                        $this->query->where($column, $comparator, $value);
                    }
                }
            }
        }
    }

    /**
     * Parse the fulltext search parameter q
     *
     * @param  string $qParam
     * @param  array  $fullTextSearchColumns
     * @return void
     */
    protected function parseFullTextSearch($qParam, $fullTextSearchColumns)
    {
        if ($qParam == '') {
            //Add where that will never be true
            $this->query->whereRaw('0 = 1');

            return;
        }

        $fulltextType = Config::get('apihandler.fulltext');

        if ($fulltextType == 'native') {
            //Use pdo's quote method to be protected against sql-injections.
            //The usual placeholders unfortunately don't seem to work using AGAINST().
            $qParam = $this->query->getConnection()->getPdo()->quote($qParam);

            //Use native fulltext search
            $this->query->whereRaw('MATCH(' . implode(',', $fullTextSearchColumns) . ') AGAINST("' . $qParam . '" IN BOOLEAN MODE)');

            //Add the * to the selects because of the score column
            if (count($this->query->columns) == 0) {
                $this->query->addSelect('*');
            }

            //Add the score column
            $scoreColumn = Config::get('apihandler.fulltext_score_column');
            $this->query->addSelect($this->query->raw('MATCH(' . implode(',', $fullTextSearchColumns) . ') AGAINST("' . $qParam . '" IN BOOLEAN MODE) as `' . $scoreColumn . '`'));
        } else {
            $keywords = explode(' ', $qParam);

            //Use default php implementation
            $this->query->where(function ($query) use ($fullTextSearchColumns, $keywords) {
                foreach ($fullTextSearchColumns as $column) {
                    foreach ($keywords as $keyword) {
                        $query->orWhere($column, 'LIKE', '%' . $keyword . '%');
                    }
                }
            });
        }
    }

    /**
     * Parse the meta parameter and prepare an array of meta provider objects.
     *
     * @param  array $metaParam
     * @return void
     */
    protected function parseConfig($configParam)
    {
        $configItems = explode(',', $configParam);

        foreach ($configItems as $configItem) {
            $configItem = trim($configItem);

            $pos = strpos($configItem, '-');
            $cat = substr($configItem, 0, $pos);
            $option = substr($configItem, $pos + 1);

            if ($cat == 'mode') {
                if ($option == 'count') {
                    $this->mode = 'count';
                }
            } else if ($cat == 'meta') {
                if ($option == 'total-count') {
                    $this->meta[] = new CountMetaProvider('Meta-Total-Count', $this->originalQuery);
                } else if ($option == 'filter-count') {
                    $this->meta[] = new CountMetaProvider('Meta-Filter-Count', $this->query);
                }
            } else if ($cat == 'response') {
                if ($option == 'envelope') {
                    $this->envelope = true;
                } else if ($option == 'default') {
                    $this->envelope = false;
                }
            }
        }
    }

    /**
     * Determine the type of the Eloquent relation
     *
     * @param  Illuminate\Database\Eloquent\Relations\Relation $relation
     * @return string
     */
    protected function getRelationType($relation)
    {
        if ($relation instanceof HasOne) {
            return 'HasOne';
        }

        if ($relation instanceof HasMany) {
            return 'HasMany';
        }

        if ($relation instanceof BelongsTo) {
            return 'BelongsTo';
        }

        if ($relation instanceof BelongsToMany) {
            return 'BelongsToMany';
        }

        if ($relation instanceof HasManyThrough) {
            return 'HasManyThrough';
        }

        if ($relation instanceof MorphOne) {
            return 'MorphOne';
        }

        if ($relation instanceof MorphMany) {
            return 'MorphMany';
        }
    }

    /**
     * Check if there exists a method marked with the "@Relation"
     * annotation on the given model.
     *
     * @param  Illuminate\Database\Eloquent\Model  $model
     * @param  string                              $relationName
     * @return boolean
     */
    protected function isRelation($model, $relationName)
    {
        if (!method_exists($model, $relationName)) {
            return false;
        }

        $reflextionObject = new ReflectionObject($model);
        $doc = $reflextionObject->getMethod($relationName)->getDocComment();

        if ($doc && strpos($doc, '@Relation') !== false) {
            return true;
        } else {
            return false;
        }
    }
}
