<?php
use \Marcelgwerder\ApiHandler\ApiHandler;

class ApiHandlerTest extends \Illuminate\Foundation\Testing\TestCase {

    public function createApplication()
    {
        $unitTesting = true;
        $testEnvironment = 'testing';
        return require __DIR__.'/../../../../bootstrap/start.php';
    }
    
    /**
     * Test if ApiHandler::parser returns Parser object
     *
     * @return Marcelgwerder\ApiHandler\Parser
     */
    public function testParse()
    {
    
        $post = new Post();
        $params = array('fields' => 'id,title');

        $parser = ApiHandler::parse($post);
        $this->assertInstanceOf('Marcelgwerder\ApiHandler\Parser', $parser);

        $parser = ApiHandler::parse($post, $params);
        $this->assertInstanceOf('Marcelgwerder\ApiHandler\Parser', $parser);

        return $parser;
    }

    /**
     * Test if the $parser->single returns Result object
     * 
     * @depends testParse
     * @return Marcelgwerder\ApiHandler\Result
     */
    public function testSingle($parser)
    {
        //Id as a parameter
        /*$result = $parser->single(2);
        $this->assertInstanceOf('Marcelgwerder\ApiHandler\Result', $result);

        //Array of column and id as a parameter
        $result = $parser->single(array('id', 2));
        $this->assertInstanceOf('Marcelgwerder\ApiHandler\Result', $result);*/
    }

    /**
     * Test if the $parser->single returns Result object
     * 
     * @depends testParse
     * @return Marcelgwerder\ApiHandler\Result
     */
    public function testMultiple($parser)
    {
        //Empty parameter
        $result = $parser->multiple();
        $this->assertInstanceOf('Marcelgwerder\ApiHandler\Result', $result);

        //Array of column and id as a parameter
        $result = $parser->multiple(array('title', 'description'));
        $this->assertInstanceOf('Marcelgwerder\ApiHandler\Result', $result);
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

        $queryBuilder = ApiHandler::parse($post, $params)->multiple(array('title','description'))->getBuilder()->getQuery();

        //Test fields
        $columns = $queryBuilder->columns;
        $this->assertContains('id', $columns);
        $this->assertContains('title', $columns);

        //Test filters
        $wheres = $queryBuilder->wheres;

        $this->assertContains(array('type' => 'Basic', 'column' => 'title', 'operator' => 'LIKE', 'value' => 'Example Title', 'boolean' => 'or'), $wheres);
        $this->assertContains(array('type' => 'Basic', 'column' => 'title', 'operator' => 'LIKE', 'value' => 'Another Title', 'boolean' => 'or'), $wheres);

        $this->assertContains(array('type' => 'Basic', 'column' => 'title', 'operator' => '=', 'value' => 'Example Title', 'boolean' => 'and'), $wheres);
        $this->assertContains(array('type' => 'Basic', 'column' => 'title', 'operator' => 'NOT LIKE', 'value' => 'Example Title', 'boolean' => 'and'), $wheres);
        
        $this->assertContains(array('type' => 'Basic', 'column' => 'title', 'operator' => '!=', 'value' => 'Example Title', 'boolean' => 'and'), $wheres);
        $this->assertContains(array('type' => 'Basic', 'column' => 'title', 'operator' => '!=', 'value' => 'Another Title', 'boolean' => 'and'), $wheres);

        $this->assertContains(array('type' => 'Basic', 'column' => 'id', 'operator' => '>=', 'value' => 5, 'boolean' => 'and'), $wheres);

        //Test limit
        $limit = $queryBuilder->limit;
        $this->assertEquals($params['limit'], $limit);

        //Test offset
        $offset = $queryBuilder->offset;
        $this->assertEquals($params['offset'], $offset);

    }

}