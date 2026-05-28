<?php

namespace Molitor\Stock\Tests\Feature;

use Molitor\Stock\Providers\StockServiceProvider;
use Tests\TestCase;

class PackageSmokeTest extends TestCase
{
    public function test_service_provider_is_loaded(): void
    {
        $this->assertTrue(class_exists(StockServiceProvider::class));
        $this->assertTrue($this->app->providerIsLoaded(StockServiceProvider::class));
    }
}

