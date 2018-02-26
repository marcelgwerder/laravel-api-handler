<?php

namespace Marcelgwerder\ApiHandler\Tests\Parsers;

use Foo\Bar\EloquentModelStub;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Marcelgwerder\ApiHandler\Facades\ApiHandler;
use Marcelgwerder\ApiHandler\Parsers\ExpansionParser;
use Marcelgwerder\ApiHandler\Tests\TestCase;
use Mockery as m;

class ExpansionParserTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->parser = new ExpansionParser(ApiHandler::from(EloquentModelStub::class));
    }

    public function testParseHasOneExpansion()
    {
        $requestStub = $this->createMock(Request::class);
        $requestStub->expects($this->any())->method('has')->willReturn(true);
        $requestStub->expects($this->any())->method('input')->willReturn('hasOneRelation[column1]');
        
        $expansions = $this->parser->parse($requestStub);

        $this->assertEquals([
            '.' => [
                'eloquent_model_stubs.id'
            ],
            'hasOneRelation' => [
                'related_eloquent_model_stubs.eloquent_model_stub_id',
                'related_eloquent_model_stubs.column1'
            ],
        ],$expansions);
    }
}
