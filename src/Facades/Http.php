<?php

namespace WebmanTech\LaravelHttpClient\Facades;

use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use InvalidArgumentException;
use support\Container;
use support\Log;
use WebmanTech\LaravelHttpClient\Guzzle\Log\CustomLogInterface;
use WebmanTech\LaravelHttpClient\Guzzle\Log\Middleware as LogMiddleware;

/**
 * @method static \GuzzleHttp\Promise\PromiseInterface response($body = null, $status = 200, $headers = [])
 * @method static \Illuminate\Http\Client\Factory fake($callback = null)
 * @method static \Illuminate\Http\Client\PendingRequest accept(string $contentType)
 * @method static \Illuminate\Http\Client\PendingRequest acceptJson()
 * @method static \Illuminate\Http\Client\PendingRequest asForm()
 * @method static \Illuminate\Http\Client\PendingRequest asJson()
 * @method static \Illuminate\Http\Client\PendingRequest asMultipart()
 * @method static \Illuminate\Http\Client\PendingRequest async()
 * @method static \Illuminate\Http\Client\PendingRequest attach(string|array $name, string $contents = '', string|null $filename = null, array $headers = [])
 * @method static \Illuminate\Http\Client\PendingRequest baseUrl(string $url)
 * @method static \Illuminate\Http\Client\PendingRequest beforeSending(callable $callback)
 * @method static \Illuminate\Http\Client\PendingRequest bodyFormat(string $format)
 * @method static \Illuminate\Http\Client\PendingRequest contentType(string $contentType)
 * @method static \Illuminate\Http\Client\PendingRequest dd()
 * @method static \Illuminate\Http\Client\PendingRequest dump()
 * @method static \Illuminate\Http\Client\PendingRequest retry(int $times, int $sleep = 0, ?callable $when = null)
 * @method static \Illuminate\Http\Client\PendingRequest sink(string|resource $to)
 * @method static \Illuminate\Http\Client\PendingRequest stub(callable $callback)
 * @method static \Illuminate\Http\Client\PendingRequest timeout(int $seconds)
 * @method static \Illuminate\Http\Client\PendingRequest withBasicAuth(string $username, string $password)
 * @method static \Illuminate\Http\Client\PendingRequest withBody(resource|string $content, string $contentType)
 * @method static \Illuminate\Http\Client\PendingRequest withCookies(array $cookies, string $domain)
 * @method static \Illuminate\Http\Client\PendingRequest withDigestAuth(string $username, string $password)
 * @method static \Illuminate\Http\Client\PendingRequest withHeaders(array $headers)
 * @method static \Illuminate\Http\Client\PendingRequest withMiddleware(callable $middleware)
 * @method static \Illuminate\Http\Client\PendingRequest withOptions(array $options)
 * @method static \Illuminate\Http\Client\PendingRequest withToken(string $token, string $type = 'Bearer')
 * @method static \Illuminate\Http\Client\PendingRequest withUserAgent(string $userAgent)
 * @method static \Illuminate\Http\Client\PendingRequest withoutRedirecting()
 * @method static \Illuminate\Http\Client\PendingRequest withoutVerifying()
 * @method static array pool(callable $callback)
 * @method static \Illuminate\Http\Client\Response delete(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response get(string $url, array|string|null $query = null)
 * @method static \Illuminate\Http\Client\Response head(string $url, array|string|null $query = null)
 * @method static \Illuminate\Http\Client\Response patch(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response post(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response put(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response send(string $method, string $url, array $options = [])
 * @method static \Illuminate\Http\Client\ResponseSequence fakeSequence(string $urlPattern = '*')
 * @method static void assertSent(callable $callback)
 * @method static void assertSentInOrder(array $callbacks)
 * @method static void assertNotSent(callable $callback)
 * @method static void assertNothingSent()
 * @method static void assertSentCount(int $count)
 * @method static void assertSequencesAreEmpty()
 *
 * @see \Illuminate\Http\Client\Factory
 * @see \Illuminate\Support\Facades\Http
 */
class Http
{
    protected static $config = null;

    /**
     * @return Factory
     */
    public static function instance(): Factory
    {
        if (static::$config === null) {
            static::$config = config('plugin.webman-tech.laravel-http-client.app', []);
        }

        $factory = Container::get(Factory::class);

        static::bootMacros($factory);

        return $factory;
    }

    public static function __callStatic($name, $arguments)
    {
        $factory = static::instance();
        if (in_array($name, [
            'delete', 'get', 'head', 'patch', 'post', 'put', 'send',
            'withOptions',
        ])) {
            // 先添加 extension 再执行对应方法
            return static::attachExtension($factory)
                ->{$name}(...$arguments);
        }
        // 先执行，再添加 extension
        $result = $factory->{$name}(...$arguments);
        if ($result instanceof PendingRequest) {
            return static::attachExtension($result);
        }
        // result
        return $result;
    }

    protected static $_macrosLoaded = [];

    /**
     * @param Factory $factory
     * @return void
     */
    protected static function bootMacros(Factory $factory): void
    {
        if (isset(static::$_macrosLoaded['macros'])) {
            return;
        }
        static::$_macrosLoaded['macros'] = true;

        $macros = static::$config['macros'] ?? [];
        if (!$macros) {
            return;
        }
        /** @var Factory $class */
        $class = get_class($factory);
        foreach ($macros as $name => $macro) {
            $class::macro($name, $macro);
        }
    }

    /**
     * @param PendingRequest|Factory $http
     * @return PendingRequest
     */
    protected static function attachExtension($http)
    {
        if ($http instanceof Factory) {
            $http = static::newPendingRequest($http);
        }
        if (!$http instanceof PendingRequest) {
            throw new InvalidArgumentException('http must be instance of PendingRequest');
        }

        $attachedExtensionOptionKey = '__attached_extension';

        $options = $http->getOptions();
        if (array_key_exists($attachedExtensionOptionKey, $options)) {
            // PendingRequest 不能多次附加 extension
            return $http;
        }

        if ($logMiddleware = static::getLogMiddleware()) {
            $http->withMiddleware($logMiddleware);
        }
        $options = array_merge(static::getDefaultOptions(), [
            $attachedExtensionOptionKey => 1,
        ]);
        $http->withOptions($options);

        return $http;
    }

    /**
     * @see Factory::newPendingRequest()
     * @param Factory $factory
     * @return PendingRequest
     */
    protected static function newPendingRequest(Factory $factory): PendingRequest
    {
        return new PendingRequest($factory);
    }

    /**
     * @return callable|null
     */
    protected static function getLogMiddleware(): ?callable
    {
        $config = static::$config['log'] ?? [];
        if (!isset($config['enable']) || !$config['enable']) {
            return null;
        }
        $config = array_merge([
            'channel' => 'default',
            'level' => 'info',
            'format' => MessageFormatter::CLF,
            'custom' => null,
        ], $config);

        if ($config['custom']) {
            $customLog = call_user_func($config['custom'], $config);
            if ($customLog instanceof CustomLogInterface) {
                return (new LogMiddleware($customLog))->__invoke();
            }
            if ($customLog instanceof \Closure) {
                return $customLog;
            }
        }

        return Middleware::log(Log::channel($config['channel']), new MessageFormatter($config['format']), $config['level']);
    }

    /**
     * @return array
     */
    protected static function getDefaultOptions(): array
    {
        return static::$config['guzzle'] ?? [];
    }
}
