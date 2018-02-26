<?php

namespace Marcelgwerder\ApiHandler\Tests;

use Marcelgwerder\ApiHandler\ApiHandlerServiceProvider;
use Marcelgwerder\ApiHandler\Facades\ApiHandler;

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
}
