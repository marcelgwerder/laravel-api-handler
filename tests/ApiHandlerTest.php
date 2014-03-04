<?php
use \Marcelgwerder\ApiHandler\ApiHandler;
use \Illuminate\Database\Connection;
use \Illuminate\Database\ConnectionResolver;
use Mockery as m;

class ApiHandlerTest extends PHPUnit_Framework_TestCase 
{
    public function setUp()
    {
        //Fake the connection the same way as laravel does:
        //tests/Database/DatabaseEloquentBuilderTest.php#L408-L418 (mockConnectionForModel($model, $database))
        $grammar = new Illuminate\Database\Query\Grammars\MySqlGrammar;
        $processor = new Illuminate\Database\Query\Processors\MySqlProcessor;
        $connection = m::mock('Illuminate\Database\ConnectionInterface', array('getQueryGrammar' => $grammar, 'getPostProcessor' => $processor));
        $resolver = m::mock('Illuminate\Database\ConnectionResolverInterface', array('connection' => $connection));

        Post::setConnectionResolver($resolver);
    }
    
    public function testParseSingle()
    {
        $post = new Post();
        $result = ApiHandler::parseSingle($post, 5, array());

        $this->assertInstanceOf('Marcelgwerder\ApiHandler\Result', $result);
    }

    public function testParseMultiple()
    {
        $post = new Post();
        $result = ApiHandler::parseMultiple($post, array(), array());

        $this->assertInstanceOf('Marcelgwerder\ApiHandler\Result', $result);
    }

    public function testGetHeaders()
    {
       /* $post = new Post();

        $params = array(
            'meta' => 'total-count,filter-count'
        );

        $headers = ApiHandler::parseMultiple($post, array('title','description'), $params)->getResponse();

        var_dump($headers);*/
    }
   
    public function testGetBuilder()
    {    
        $post = new Post();

        $params = array(
            //Fields
            'fields' => 'id,title',
            //Filters
            'title-lk' => 'Example Title|Another Title',
            'title' => 'Example Title',
            'title-not-lk' => 'Example Title',
            'title-not' => 'Example Title|Another Title',
            'id-min' => 5,
            'id-max' => 6,
            'id-gt' => 7,
            'id-st' => 8,
            //Pagination
            'limit' => 5,
            'offset' => 10,
        );

        $queryBuilder = ApiHandler::parseMultiple($post, array('title','description'), $params)->getBuilder()->getQuery();

        //
        // Fields
        //
        
        $columns = $queryBuilder->columns;

        $this->assertContains('id', $columns);
        $this->assertContains('title', $columns);

        //
        // Filters
        //

        $wheres = $queryBuilder->wheres;

        //Test the nested filters
        foreach($wheres as $where)
        {
            if($where['type'] == 'Nested')
            {
                $query = $where['query'];
                $subWheres = $query->wheres;

                if($subWheres[0]['boolean'] == 'and')
                {
                    //assert for title-not
                    $this->assertEquals(array('type' => 'Basic', 'column' => 'title', 'operator' => '!=', 'value' => 'Example Title', 'boolean' => 'and'), $subWheres[0]);
                    $this->assertEquals(array('type' => 'Basic', 'column' => 'title', 'operator' => '!=', 'value' => 'Another Title', 'boolean' => 'and'), $subWheres[1]);   
                }
                else
                {
                    //assert for title-lk
                    $this->assertEquals(array('type' => 'Basic', 'column' => 'title', 'operator' => 'LIKE', 'value' => 'Example Title', 'boolean' => 'or'), $subWheres[0]);
                    $this->assertEquals(array('type' => 'Basic', 'column' => 'title', 'operator' => 'LIKE', 'value' => 'Another Title', 'boolean' => 'or'), $subWheres[1]);  
                }

            }
        }

        //assert for title
        $this->assertContains(array('type' => 'Basic', 'column' => 'title', 'operator' => '=', 'value' => 'Example Title', 'boolean' => 'and'), $wheres);
        //assert for title-not-lk
        $this->assertContains(array('type' => 'Basic', 'column' => 'title', 'operator' => 'NOT LIKE', 'value' => 'Example Title', 'boolean' => 'and'), $wheres);
        
        //assert for id-min
        $this->assertContains(array('type' => 'Basic', 'column' => 'id', 'operator' => '>=', 'value' => 5, 'boolean' => 'and'), $wheres);

        //assert for id-max
        $this->assertContains(array('type' => 'Basic', 'column' => 'id', 'operator' => '<=', 'value' => 6, 'boolean' => 'and'), $wheres);

        //assert for id-gt
        $this->assertContains(array('type' => 'Basic', 'column' => 'id', 'operator' => '>', 'value' => 7, 'boolean' => 'and'), $wheres);

        //assert for id-st
        $this->assertContains(array('type' => 'Basic', 'column' => 'id', 'operator' => '<', 'value' => 8, 'boolean' => 'and'), $wheres);

        //
        // Limit
        //
        
        $limit = $queryBuilder->limit;
        $this->assertEquals($params['limit'], $limit);

        //
        // Offset
        //
        
        $offset = $queryBuilder->offset;
        $this->assertEquals($params['offset'], $offset);

    }

}