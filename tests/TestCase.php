<?php

namespace Marcelgwerder\ApiHandler\Tests;

use Marcelgwerder\ApiHandler\ApiHandlerServiceProvider;
use Marcelgwerder\ApiHandler\Facades\ApiHandler;
use Illuminate\Http\Request;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp()
    {
        parent::setUp();

        require_once __DIR__.'/Database/stubs/EloquentModelStub.php';
        require_once __DIR__.'/Database/stubs/IntermediateEloquentModelStub.php';
        require_once __DIR__.'/Database/stubs/RelatedEloquentModelStub.php';
    }

    protected function getPackageProviders($app)
    {
        return [ApiHandlerServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'ApiHandler' => ApiHandler::class,
        ];
    }

    protected function createRequestStubForValue($param) {
        $stub = $this->createMock(Request::class);
        $stub->expects($this->any())->method('has')->willReturn(true);
        $stub->expects($this->any())->method('input')->willReturn($param);

        return $stub;
    }

}
