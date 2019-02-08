<?php

namespace Marcelgwerder\ApiHandler\Tests\Parsers;

use Foo\Bar\EloquentModelStub;
use Foo\Bar\RelatedEloquentModelStub;
use Marcelgwerder\ApiHandler\Tests\TestCase;
use Marcelgwerder\ApiHandler\Facades\ApiHandler;
use Marcelgwerder\ApiHandler\Parsers\ExpansionParser;

class ExpansionParserTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testParseHasOneExpansion()
    {
        $parser = new ExpansionParser(ApiHandler::from(EloquentModelStub::class));
        $requestStub = $this->createRequestStubForValue('hasOneRelation[column1]');

        $expansions = $parser->parse($requestStub);

        $this->assertEquals([
            '.' => [
                'model.local_key',
            ],
            'hasOneRelation' => [
                'related_model.foreign_key',
                'related_model.column1',
            ],
        ], $expansions);
    }

    public function testParseBelongsToExpansion()
    {
        $parser = new ExpansionParser(ApiHandler::from(RelatedEloquentModelStub::class));
        $requestStub = $this->createRequestStubForValue('belongsToRelation[column1]');

        $expansions = $parser->parse($requestStub);

        $this->assertEquals([
            '.' => [
                'related_model.foreign_key',
            ],
            'belongsToRelation' => [
                'model.other_key',
                'model.column1',
            ],
        ], $expansions);
    }

    public function testParseHasManyExpansion()
    {
        $parser = new ExpansionParser(ApiHandler::from(EloquentModelStub::class));
        $requestStub = $this->createRequestStubForValue('hasManyRelation[column1]');

        $expansions = $parser->parse($requestStub);

        $this->assertEquals([
            '.' => [
                'model.local_key',
            ],
            'hasManyRelation' => [
                'related_model.foreign_key',
                'related_model.column1',
            ],
        ], $expansions);
    }

    public function testParseBelongsToManyExpansion()
    {
        $parser = new ExpansionParser(ApiHandler::from(EloquentModelStub::class));
        $requestStub = $this->createRequestStubForValue('belongsToManyRelation[column1]');

        $expansions = $parser->parse($requestStub);

        $this->assertEquals([
            '.' => [
                'model.id',
            ],
            'belongsToManyRelation' => [
                'related_model.id',
                'related_model.column1',
            ],
        ], $expansions);
    }

    public function testParseHasManyThroughExpansion()
    {
        $parser = new ExpansionParser(ApiHandler::from(EloquentModelStub::class));
        $requestStub = $this->createRequestStubForValue('hasManyThroughRelation[column1]');

        $expansions = $parser->parse($requestStub);

        $this->assertEquals([
            '.' => [
                'model.local_key',
            ],
            'hasManyThroughRelation' => [
                'related_model.related_foreign_key',
                'related_model.column1',
            ],
        ], $expansions);
    }

    public function testParseMorphManyExpansion()
    {
        $parser = new ExpansionParser(ApiHandler::from(EloquentModelStub::class));
        $requestStub = $this->createRequestStubForValue('morphManyRelation[column1]');

        $expansions = $parser->parse($requestStub);

        $this->assertEquals([
            '.' => [
                'model.id',
            ],
            'morphManyRelation' => [
                'related_model.id',
                'related_model.column1',
            ],
        ], $expansions);
    }

    public function testParseMorphToExpansion()
    {
        $parser = new ExpansionParser(ApiHandler::from(RelatedEloquentModelStub::class));
        $requestStub = $this->createRequestStubForValue('morphToRelation[column1]');

        $expansions = $parser->parse($requestStub);

        $this->assertEquals([
            '.' => [
                'related_model.morph_to_relation_id',
                'related_model.morph_to_relation_type',
            ],
            // Tables are wrong because the related model names are stored in the db
            'morphToRelation' => [
                'related_model.id',
                'related_model.column1',
            ],
        ], $expansions);
    }

    public function testParseMorphToManyExpansion()
    {
        $parser = new ExpansionParser(ApiHandler::from(EloquentModelStub::class));
        $requestStub = $this->createRequestStubForValue('morphToManyRelation[column1]');

        $expansions = $parser->parse($requestStub);

        $this->assertEquals([
            '.' => [
                'model.id',
            ],
            'morphToManyRelation' => [
                'related_model.id',
                'related_model.column1',
            ],
        ], $expansions);
    }

    public function testParseMorphedByManyExpansion()
    {
        $parser = new ExpansionParser(ApiHandler::from(RelatedEloquentModelStub::class));
        $requestStub = $this->createRequestStubForValue('morphedByManyRelation[column1]');

        $expansions = $parser->parse($requestStub);

        $this->assertEquals([
            '.' => [
                'related_model.id',
            ],
            'morphedByManyRelation' => [
                'model.id',
                'model.column1',
            ],
        ], $expansions);
    }
}
