<?php namespace Marcelgwerder\ApiHandler;

use Illuminate\Support\Facades\Response;

class Result
{
    /**
     * Parser instance.
     *
     * @var Marcelgwerder\ApiHandler\Parser
     */
    protected $parser;

    /**
     * Create a new result
     *
     * @param  Marcelgwerder\ApiHandler\Parser $parse
     * @return void
     */
    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Return a laravel response object including the correct status code and headers
     *
     * @return Illuminate\Support\Facades\Response
     */
    public function getResponse()
    {
        $headers = $this->getHeaders();

        if ($this->parser->mode == 'count') {
            return Response::json($headers, 200, $headers);
        } else {
            if ($this->parser->envelope) {
                return Response::json([
                    'meta' => $headers,
                    'data' => $this->getResult(),
                ], 200);
            } else {
                return Response::json($this->getResult(), 200, $headers);
            }

        }
    }

    /**
     * Return the query builder including the results
     *
     * @return Illuminate\Database\Query\Builder $result
     */
    public function getResult()
    {
        if ($this->parser->multiple) {
            $result = $this->parser->builder->get();
        } else {
            $result = $this->parser->builder->first();
        }

        return $result;
    }

    /**
     * Get the query bulder object
     *
     * @return Illuminate\Database\Query\Builder
     */
    public function getBuilder()
    {
        return $this->parser->builder;
    }

    /**
     * Get the headers
     *
     * @return array
     */
    public function getHeaders()
    {
        $meta = $this->parser->meta;
        $headers = [];

        foreach ($meta as $provider) {
            if ($this->parser->envelope) {
                $headers[strtolower(str_replace('-', '_', preg_replace('/^Meta-/', '', $provider->getTitle())))] = $provider->get();
            } else {
                $headers[$provider->getTitle()] = $provider->get();
            }
        }

        return $headers;
    }

    /**
     * Get an array of meta providers
     *
     * @return array
     */
    public function getMetaProviders()
    {
        return $this->parser->meta;
    }

    /**
     * Get the mode of the parser
     *
     * @return string
     */
    public function getMode()
    {
        return $this->parser->mode;
    }
}
