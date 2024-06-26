<?php

namespace WebmanTech\LaravelHttpClient\Tests\Facades;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use PHPUnit\Framework\TestCase;
use WebmanTech\LaravelHttpClient\Facades\Http;

/**
 * https://laravel.com/docs/10.x/http-client
 */
class HttpTest extends TestCase
{
    // 支持 https://httpbingo.org 和 https://httpbin.org
    // 选择一个稳定的
    const HTTP_BIN_HOST = 'https://httpbingo.org';

    private $httpBinHost = self::HTTP_BIN_HOST;

    public function testInstance()
    {
        $this->assertInstanceOf(HttpFactory::class, Http::instance());
    }

    public function testHttpMethods()
    {
        foreach (['get', 'post', 'patch', 'put', 'delete'] as $method) {
            $url = "{$this->httpBinHost}/{$method}";
            $this->assertEquals($url, Http::{$method}($url)['url']);
        }
    }

    private function getHeader($data, $key): ?string
    {
        $headerValues = Arr::wrap($data[$key]);
        return $headerValues[0] ?? null;
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
            $url = "{$this->httpBinHost}/status/{$status}";
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
        $response = Http::get("{$this->httpBinHost}/anything", $data);
        $this->assertEquals($data['name'], Arr::wrap($response['args']['name'])[0] ?? null);
        // post json
        $response = Http::post("{$this->httpBinHost}/anything", $data);
        $this->assertEquals($data, $response['json']);
        // post form
        $response = Http::asForm()->post("{$this->httpBinHost}/anything", $data);
        $this->assertEquals($data['name'], Arr::wrap($response['form']['name'])[0] ?? null);
        // post rawBody
        $response = Http::withBody('xxxx', 'text/plain')->post("{$this->httpBinHost}/anything");
        $this->assertEquals('xxxx', $response['data']);
        // post Multi-Part files
        $response = Http::attach(
            'file1', file_get_contents(__DIR__ . '/../fixtures/test.txt'), 'test.txt',
        )->post("{$this->httpBinHost}/anything");
        $this->assertEquals(['file1'], array_keys($response['files']));
    }

    public function testRequestHeaders()
    {
        // 自定义 header
        $response = Http::withHeaders([
            'X-First' => 'foo',
        ])->get("{$this->httpBinHost}/anything");
        $this->assertEquals('foo', $this->getHeader($response['headers'], 'X-First'));

        // accept
        $response = Http::accept('text/html')->get("{$this->httpBinHost}/anything");
        $this->assertEquals('text/html', $this->getHeader($response['headers'], 'Accept'));

        // acceptJson
        $response = Http::acceptJson()->get("{$this->httpBinHost}/anything");
        $this->assertEquals('application/json', $this->getHeader($response['headers'], 'Accept'));
    }

    public function testAuthentication()
    {
        // basic auth
        $response = Http::withBasicAuth('user', 'pass')->get("{$this->httpBinHost}/basic-auth/user/pass");
        $this->assertTrue($response->successful());

        $response = Http::withDigestAuth('user', 'pass')->get("{$this->httpBinHost}/digest-auth/auth/user/pass/MD5");
        $this->assertTrue($response->successful());

        // bearer
        $response = Http::withToken('token')->get("{$this->httpBinHost}/bearer");
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
        $response = Http::get("{$this->httpBinHost}/status/500");
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
                $pool->get("{$this->httpBinHost}/anything"),
                $pool->get("{$this->httpBinHost}/anything"),
            ];
        });
        $this->assertTrue($responses[0]->ok() && $responses[1]->ok());

        $responses = Http::pool(function (Pool $pool) {
            return [
                $pool->as('first')->get("{$this->httpBinHost}/anything"),
                $pool->as('second')->get("{$this->httpBinHost}/anything"),
            ];
        });
        $this->assertTrue($responses['first']->ok() && $responses['second']->ok());
    }

    public function testMacro()
    {
        // macro 已经通过 config 配置
        $this->assertTrue(Http::httpbin()->get('anything')->ok());
    }

    private function ensureLogFile(bool $reset = true)
    {
        $date = date('Y-m-d');
        $logFile = runtime_path() . "/logs/webman-{$date}.log";
        if (!file_exists($logFile)) {
            $dirname = dirname($logFile);
            if (!is_dir($dirname)) {
                mkdir(dirname($logFile), 0777, true);
            }
            file_put_contents($logFile, '');
        }
        if ($reset) {
            file_put_contents($logFile, '');
        }
        return $logFile;
    }

    public function testLog()
    {
        $logFile = $this->ensureLogFile();
        $logSize = strlen(file_get_contents($logFile));

        $assetLogged = function (bool $is) use ($logFile, &$logSize) {
            $newLogSize = strlen(file_get_contents($logFile));
            // 通过比较日志大小来判断是否记了日志
            $this->assertIsBool($is, ($newLogSize - $logSize) > 10);
            $logSize = $newLogSize;
        };

        // 直接发请求的记录日志
        Http::get("{$this->httpBinHost}/anything");
        $assetLogged(true);

        // macro 形式的记录日志
        Http::httpbin()->get('anything');
        $assetLogged(true);

        // pool 目前不会记录日志
        Http::pool(function (Pool $pool) {
            $pool->get("{$this->httpBinHost}/anything");
        });
        $assetLogged(false);
    }

    public function testDifferentFormatter()
    {
        $logFile = $this->ensureLogFile();

        Http::post("{$this->httpBinHost}/anything", [
            'a' => 'b'
        ]);
        // 人工检查日志格式是不是 psr 的
        $this->assertTrue(true);

        // 此处是在 config 中配置了 query 中有 use_json_formatter=1 的，使用 json 格式的日志
        Http::post("{$this->httpBinHost}/anything?use_json_formatter=1", [
            'password' => '123456'
        ]);
        // 人工检查日志格式是不是 json 的
        $this->assertTrue(true);
        // 检查 password 是否被替换
        $content = file_get_contents($logFile);
        $this->assertStringContainsString('\"password\":\"***\"', $content);

        Http::attach(
            'file', file_get_contents(__DIR__ . '/../fixtures/test.txt')
        )->post("{$this->httpBinHost}/anything", [
            'a' => 'b'
        ]);
        // 人工检查日志是否将 form 的 body 屏蔽掉了
        $this->assertTrue(true);
    }
}
