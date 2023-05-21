<?php

namespace WebmanTech\LaravelHttpClient\Tests\Facades;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use WebmanTech\LaravelHttpClient\Facades\Http;

/**
 * https://laravel.com/docs/10.x/http-client
 */
class HttpTest extends TestCase
{
    public function testInstance()
    {
        $this->assertInstanceOf(HttpFactory::class, Http::instance());
    }
}
