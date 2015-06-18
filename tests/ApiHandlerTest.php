<?php
use Mockery as m;
use \Illuminate\Database\Eloquent\Collection;
use \Illuminate\Database\Query\Expression;
use \Illuminate\Http\JsonResponse;
use \Illuminate\Support\Facades\Config;
use \Illuminate\Support\Facades\Input;
use \Illuminate\Support\Facades\Response;
use \Marcelgwerder\ApiHandler\ApiHandler;

class ApiHandlerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        //Test parameters
        $this->params = [
            //Fields
            '_fields' => 'title,description,comments.title,user.first_name',
            //Filters
            'title-lk' => 'Example Title|Another Title',
            'title' => 'Example Title',
            'title-not-lk' => 'Example Title',
            'title-not' => 'Example Title|Another Title',
            'id-min' => 5,
            'id-max' => 6,
            'id-gt' => 7,
            'id-st' => 8,
            'id-in' => '1,2',
            'id-not-in' => '3,4',
            //Pagination
            '_limit' => 5,
            '_offset' => 10,
            //With
            '_with' => 'comments.user',
            //Sort
            '_sort' => '-title,first_name,comments.created_at',
            //Config
            '_config' => 'mode-default,meta-filter-count,meta-total-count',
        ];

        $this->fulltextSelectExpression = new Expression('MATCH(title,description) AGAINST("Something to search" IN BOOLEAN MODE) as `_score`');

        //Test data
        $this->data = [
            ['foo' => 'A1', 'bar' => 'B1'],
            ['foo' => 'A2', 'bar' => 'B2'],
        ];

        //Mock the application
        $app = m::mock('AppMock');
        $app->shouldReceive('instance')->once()->andReturn($app);
        Illuminate\Support\Facades\Facade::setFacadeApplication($app);

        //Mock the config
        $config = m::mock('ConfigMock');
        $config->shouldReceive('get')->once()
               ->with('apihandler.prefix')->andReturn('_');
        $config->shouldReceive('get')->once()
               ->with('apihandler.envelope')->andReturn(false);
        $config->shouldReceive('get')->once()
               ->with('apihandler.fulltext')->andReturn('default');
        $config->shouldReceive('get')->once()
               ->with('apihandler.fulltext')->andReturn('native');
        $config->shouldReceive('get')->once()
               ->with('apihandler.fulltext_score_column')->andReturn('_score');
        Config::swap($config);

        $app->shouldReceive('make')->once()->andReturn($config);

        //Mock the input
        $input = m::mock('InputMock');
        $input->shouldReceive('get')->once()
              ->with()->andReturn($this->params);
        Input::swap($input);

        //Mock the response
        $response = m::mock('ResponseMock');
        $response->shouldReceive('json')->once()->andReturn(new JsonResponse(['meta' => [], 'data' => new Collection()]));
        Response::swap($response);

        //Mock pdo
        $pdo = m::mock('PdoMock');
        $pdo->shouldReceive('quote')->once()
            ->with('Something to search')->andReturn('Something to search');

        //Mock the connection the same way as laravel does:
        //tests/Database/DatabaseEloquentBuilderTest.php#L408-L418 (mockConnectionForModel($model, $database))
        $grammar = new Illuminate\Database\Query\Grammars\MySqlGrammar;
        $processor = new Illuminate\Database\Query\Processors\MySqlProcessor;
        $connection = m::mock('Illuminate\Database\ConnectionInterface', ['getQueryGrammar' => $grammar, 'getPostProcessor' => $processor]);
        $connection->shouldReceive('select')->once()->with('select * from `posts`', [])->andReturn($this->data);
        $connection->shouldReceive('select')->once()->with('select * from `posts`', [], true)->andReturn($this->data);
        $connection->shouldReceive('raw')->once()->with('MATCH(title,description) AGAINST("Something to search" IN BOOLEAN MODE) as `_score`')
                   ->andReturn($this->fulltextSelectExpression);
        $connection->shouldReceive('getPdo')->once()->andReturn($pdo);

        $resolver = m::mock('Illuminate\Database\ConnectionResolverInterface', ['connection' => $connection]);

        Post::setConnectionResolver($resolver);

        $this->apiHandler = new ApiHandler();
    }

    public function testParseSingle()
    {
        $post = new Post();
        $result = $this->apiHandler->parseSingle($post, 5, []);

        $this->assertInstanceOf('Marcelgwerder\ApiHandler\Result', $result);
    }

    public function testParseMultiple()
    {
        $post = new Post();
        $result = $this->apiHandler->parseMultiple($post, [], []);

        $this->assertInstanceOf('Marcelgwerder\ApiHandler\Result', $result);
    }

    public function testGetBuilder()
    {

        $post = new Post();

        $builder = $this->apiHandler->parseMultiple($post, ['title', 'description'], $this->params)->getBuilder();
        $queryBuilder = $builder->getQuery();

        //
        // Fields
        //

        $columns = $queryBuilder->columns;

        $this->assertContains('description', $columns);
        $this->assertContains('title', $columns);

        //
        // Filters
        //

        $wheres = $queryBuilder->wheres;

        //Test the nested filters
        foreach ($wheres as $where) {
            if ($where['type'] == 'Nested') {
                $query = $where['query'];
                $subWheres = $query->wheres;

                if ($subWheres[0]['boolean'] == 'and') {
                    //assert for title-not
                    $this->assertEquals(['type' => 'Basic', 'column' => 'title', 'operator' => '!=', 'value' => 'Example Title', 'boolean' => 'and'], $subWheres[0]);
                    $this->assertEquals(['type' => 'Basic', 'column' => 'title', 'operator' => '!=', 'value' => 'Another Title', 'boolean' => 'and'], $subWheres[1]);
                } else {
                    //assert for title-lk
                    $this->assertEquals(['type' => 'Basic', 'column' => 'title', 'operator' => 'LIKE', 'value' => 'Example Title', 'boolean' => 'or'], $subWheres[0]);
                    $this->assertEquals(['type' => 'Basic', 'column' => 'title', 'operator' => 'LIKE', 'value' => 'Another Title', 'boolean' => 'or'], $subWheres[1]);
                }

            }
        }

        //assert for title
        $this->assertContains(['type' => 'Basic', 'column' => 'title', 'operator' => '=', 'value' => 'Example Title', 'boolean' => 'and'], $wheres);
        //assert for title-not-lk
        $this->assertContains(['type' => 'Basic', 'column' => 'title', 'operator' => 'NOT LIKE', 'value' => 'Example Title', 'boolean' => 'and'], $wheres);

        //assert for id-min
        $this->assertContains(['type' => 'Basic', 'column' => 'id', 'operator' => '>=', 'value' => 5, 'boolean' => 'and'], $wheres);

        //assert for id-max
        $this->assertContains(['type' => 'Basic', 'column' => 'id', 'operator' => '<=', 'value' => 6, 'boolean' => 'and'], $wheres);

        //assert for id-gt
        $this->assertContains(['type' => 'Basic', 'column' => 'id', 'operator' => '>', 'value' => 7, 'boolean' => 'and'], $wheres);

        //assert for id-st
        $this->assertContains(['type' => 'Basic', 'column' => 'id', 'operator' => '<', 'value' => 8, 'boolean' => 'and'], $wheres);

        //assert for id-in
        $this->assertContains(['type' => 'In', 'column' => 'id', 'values' => ['1', '2'], 'boolean' => 'and'], $wheres);

        //assert for id-not-in
        $this->assertContains(['type' => 'NotIn', 'column' => 'id', 'values' => ['3', '4'], 'boolean' => 'and'], $wheres);

        //
        // Limit
        //

        $limit = $queryBuilder->limit;
        $this->assertEquals($this->params['_limit'], $limit);

        //
        // Offset
        //

        $offset = $queryBuilder->offset;
        $this->assertEquals($this->params['_offset'], $offset);

        //
        // Sort
        //

        $orders = $queryBuilder->orders;
        $this->assertContains(['column' => 'title', 'direction' => 'desc'], $orders);
        $this->assertContains(['column' => 'first_name', 'direction' => 'asc'], $orders);

        //
        //With
        //

        $eagerLoads = $builder->getEagerLoads();

        $this->assertArrayHasKey('comments', $eagerLoads);
        $this->assertArrayHasKey('comments.user', $eagerLoads);

        //Check if auto fields are set on the base query
        $this->assertContains('posts.id', $columns);

        //Check if fields are set on the "comments" relation query
        $query = $post->newQuery();
        call_user_func($eagerLoads['comments'], $query);
        $columns = $query->getQuery()->columns;

        $this->assertContains('title', $columns);
        $this->assertContains('customfk_post_id', $columns);
        $this->assertContains('user_id', $columns);

        //Check if fields are set on the "comments.user" relation query
        $query = $post->newQuery();
        call_user_func($eagerLoads['comments.user'], $query);
        $columns = $query->getQuery()->columns;

        $this->assertContains('id', $columns);
        $this->assertContains('first_name', $columns);

        //Check if sorts are set on the "comments" relation query
        $query = $post->newQuery();
        call_user_func($eagerLoads['comments'], $query);
        $orders = $query->getQuery()->orders;
        $this->assertContains(['column' => 'created_at', 'direction' => 'asc'], $orders);

        //
        // Fulltext search
        //

        $builder = $this->apiHandler->parseMultiple($post, ['title', 'description'], ['_q' => 'Something to search'])->getBuilder();
        $queryBuilder = $builder->getQuery();

        $wheres = $queryBuilder->wheres;

        //Test the nested filters
        foreach ($wheres as $where) {
            if ($where['type'] == 'Nested') {
                $query = $where['query'];
                $subWheres = $query->wheres;

                $this->assertEquals(['type' => 'Basic', 'column' => 'title', 'operator' => 'LIKE', 'value' => '%Something%', 'boolean' => 'or'], $subWheres[0]);
                $this->assertEquals(['type' => 'Basic', 'column' => 'title', 'operator' => 'LIKE', 'value' => '%to%', 'boolean' => 'or'], $subWheres[1]);
                $this->assertEquals(['type' => 'Basic', 'column' => 'title', 'operator' => 'LIKE', 'value' => '%search%', 'boolean' => 'or'], $subWheres[2]);
                $this->assertEquals(['type' => 'Basic', 'column' => 'description', 'operator' => 'LIKE', 'value' => '%Something%', 'boolean' => 'or'], $subWheres[3]);
                $this->assertEquals(['type' => 'Basic', 'column' => 'description', 'operator' => 'LIKE', 'value' => '%to%', 'boolean' => 'or'], $subWheres[4]);
                $this->assertEquals(['type' => 'Basic', 'column' => 'description', 'operator' => 'LIKE', 'value' => '%search%', 'boolean' => 'or'], $subWheres[5]);
            }
        }

        $builder = $this->apiHandler->parseMultiple($post, ['title', 'description'], ['_q' => 'Something to search'])->getBuilder();
        $queryBuilder = $builder->getQuery();

        //Test the where
        $wheres = $queryBuilder->wheres;
        $this->assertEquals(['type' => 'raw', 'sql' => 'MATCH(title,description) AGAINST("Something to search" IN BOOLEAN MODE)', 'boolean' => 'and'], $wheres[0]);

        //Test the select
        $columns = $queryBuilder->columns;
        $this->assertContains($this->fulltextSelectExpression, $columns);
        $this->assertContains('*', $columns);
    }

    public function testGetResponse()
    {
        $post = new Post();

        $response = $this->apiHandler->parseMultiple($post, ['title', 'description'], ['_config' => 'response-envelope'])->getResponse();
        $data = $response->getData();

        $this->assertInstanceOf('Illuminate\Http\JsonResponse', $response);
        $this->assertObjectHasAttribute('meta', $data);
        $this->assertObjectHasAttribute('data', $data);
    }
}
