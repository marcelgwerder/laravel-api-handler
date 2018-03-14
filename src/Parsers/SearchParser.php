<?php

namespace Marcelgwerder\ApiHandler\Parsers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\Request;
use Marcelgwerder\ApiHandler\Exceptions\InvalidSearchException;

class SearchParser extends Parser
{
    /**
     * The search term which which should be searched for.
     *
     * @var int
     */
    protected $searchTerm;

    /**
     * Parse the pagination query parameters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function parse(Request $request): ?array
    {
        $this->searchTerm = $request->get('search', null);

        if ($this->searchTerm !== null && empty($this->handler->config->get('searchable'))) {
            throw new InvalidSearchException('There are no searchable columns for this endpoint.');
        }

        return [
            'query' => $this->searchTerm,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if ($this->searchTerm === null) {
            // If the search query is not set, we just do nothing.
            return;
        } else if (trim($this->searchTerm) === '') {
            // Make sure an empty search query results in an empty response.
            // We simply achieve this by adding a where condition that will never be true.
            $builder->whereRaw('0 = 1');

            return;
        }

        $escapedSearchTerm = $builder->getConnection()->getPdo()->quote($this->searchTerm);

        if ($this->handler->config->get('search_driver') === 'native') {
            $scoreColumn = $this->handler->config->get('search_score_column');
            $searchableColumns = implode(',', array_map(function ($column) {
                return "`$column`";
            }, $this->handler->config->get('searchable')));

            $matchAgainst = "MATCH ($searchableColumns) AGAINST (\"$escapedSearchTerm\" IN BOOLEAN MODE)";

            $queryBuilder = $builder->getQuery();

            // Count the existing selects before adding the match
            $columnCount = count($queryBuilder->columns ?: []);
            $searchScoreIndex = array_search($scoreColumn, array_column($queryBuilder->orders ?: [], 'column'));

            if($columnCount === 0 || $searchScoreIndex !== false) {
                // There are two cases where we need the search score column:
                // A: The column is needed in the response
                // B: We want to sort by the column
                $builder->selectRaw("$matchAgainst AS `$scoreColumn`");
            } else {
                // If there the query is already limited by columns, look for the search score column and replace it with
                // the the proper match against expression to be able to select the search score column by its alias.
                $searchScoreIndex = array_search($scoreColumn, $queryBuilder->columns ?: []);

                if($searchScoreIndex !== false) {
                    $queryBuilder->columns[$searchScoreIndex] = new Expression("$matchAgainst AS `$scoreColumn`");
                }       
            }     
            
            $builder->whereRaw($matchAgainst);

            if ($columnCount === 0) {
                // Of there is no column in the select statement before adding the match,
                // we have to make sure all columns of the table are fetched which is the default.
                $builder->addSelect("$queryBuilder->from.*");
            }
        } else {
            // TODO: Implement alternative search drivers
        }
    }
}
