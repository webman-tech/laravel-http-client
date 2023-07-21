<?php

namespace WebmanTech\LaravelHttpClient\Tests\Facades;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\RequestException;
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

    public function testHttpMethods()
    {
        foreach (['get', 'post', 'patch', 'put', 'delete'] as $method) {
            $url = "https://httpbin.org/{$method}";
            $this->assertEquals($url, Http::{$method}($url)['url']);
        }
    }

    public function testHttpStatusCode()
    {
        $map = [
            200 => 'successful',
            310 => 'redirect',
            422 => ['failed', 'clientError'],
            500 => ['failed', 'serverError'],
        ];
        foreach ($map as $status => $resultFns) {
            $url = "https://httpbin.org/status/{$status}";
            $response = Http::get($url);
            $this->assertEquals($status, $response->status());
            foreach ((array)$resultFns as $fn) {
                $this->assertEquals($fn, $response->{$fn}() ? $fn : '');
            }
        }
    }

    public function testRequestData()
    {
        $data = [
            'name' => 'webman',
        ];
        // get query
        $response = Http::get('https://httpbin.org/anything', $data);
        $this->assertEquals($data, $response['args']);
        // post json
        $response = Http::post('https://httpbin.org/anything', $data);
        $this->assertEquals($data, $response['json']);
        // post form
        $response = Http::asForm()->post('https://httpbin.org/anything', $data);
        $this->assertEquals($data, $response['form']);
        // post rawBody
        $response = Http::withBody('xxxx', 'text/plain')->post('https://httpbin.org/anything');
        $this->assertEquals('xxxx', $response['data']);
        // post Multi-Part files
        $response = Http::attach(
            'file1', file_get_contents(__DIR__ . '/../fixtures/test.txt'), 'test.txt',
        )->post('https://httpbin.org/anything');
        $this->assertEquals(['file1'], array_keys($response['files']));
    }

    public function testRequestHeaders()
    {
        // 自定义 header
        $response = Http::withHeaders([
            'X-First' => 'foo',
        ])->get('https://httpbin.org/anything');
        $this->assertEquals('foo', $response['headers']['X-First']);

        // accept
        $response = Http::accept('text/html')->get('https://httpbin.org/anything');
        $this->assertEquals('text/html', $response['headers']['Accept']);

        // acceptJson
        $response = Http::acceptJson()->get('https://httpbin.org/anything');
        $this->assertEquals('application/json', $response['headers']['Accept']);
    }

    public function testAuthentication()
    {
        // basic auth
        $response = Http::withBasicAuth('user', 'pass')->get('https://httpbin.org/basic-auth/user/pass');
        $this->assertTrue($response->successful());

        $response = Http::withDigestAuth('user', 'pass')->get('https://httpbin.org/digest-auth/undefined/user/pass');
        $this->assertTrue($response->successful());

        // bearer
        $response = Http::withToken('token')->get('https://httpbin.org/bearer');
        $this->assertTrue($response->successful());
    }

    public function testPending()
    {
        // 以下情况不好测，仅确保方法存在
        $this->assertInstanceOf(PendingRequest::class, Http::timeout(3));
        $this->assertInstanceOf(PendingRequest::class, Http::retry(3));
        $this->assertInstanceOf(PendingRequest::class, Http::retry(3, 10));
        $this->assertInstanceOf(PendingRequest::class, Http::retry(3, 10, function ($e) {
            return $e instanceof ConnectionException;
        }));
    }

    public function testErrorHandling()
    {
        $response = Http::get('https://httpbin.org/status/500');
        // onError
        $response->onError(function () {
            $this->assertTrue(true);
        });
        // throw
        try {
            $response->throw();
        } catch (\Throwable $e) {
            $this->assertInstanceOf(RequestException::class, $e);
        }
        // throwIf
        try {
            $response->throwIf(true);
        } catch (\Throwable $e) {
            $this->assertInstanceOf(RequestException::class, $e);
        }
        // 其他 throwUnless throwIfStatus 不写了
    }

    public function testPool()
    {
        $responses = Http::pool(function (Pool $pool) {
            return [
                $pool->get('https://httpbin.org/anything'),
                $pool->get('https://httpbin.org/anything'),
            ];
        });
        $this->assertTrue($responses[0]->ok() && $responses[1]->ok());

        $responses = Http::pool(function (Pool $pool) {
            return [
                $pool->as('first')->get('https://httpbin.org/anything'),
                $pool->as('second')->get('https://httpbin.org/anything'),
            ];
        });
        $this->assertTrue($responses['first']->ok() && $responses['second']->ok());
    }

    public function testMacro()
    {
        // macro 已经通过 config 配置
        $this->assertTrue(Http::httpbin()->get('anything')->ok());
    }

    public function testLog()
    {
        $date = date('Y-m-d');
        $logFile = runtime_path() . "/logs/webman-{$date}.log";

        // 前面的测试可能已经产生了 logFile，所以要删掉
        if (file_exists($logFile)) {
            unlink($logFile);
        }

        // 已经通过 config 配置
        Http::get('https://httpbin.org/anything');
        $this->assertTrue(file_exists($logFile));
    }
}
