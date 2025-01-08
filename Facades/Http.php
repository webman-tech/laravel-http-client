<?php

namespace WebmanTech\LaravelHttpClient\Facades;

use Closure;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use support\Container;
use support\Log;
use WebmanTech\LaravelHttpClient\Guzzle\Log\CustomLogInterface;
use WebmanTech\LaravelHttpClient\Guzzle\Log\Middleware as LogMiddleware;
use WebmanTech\LaravelHttpClient\Helper\ConfigHelper;

/**
 * @method static \Illuminate\Http\Client\Factory globalMiddleware(callable $middleware)
 * @method static \Illuminate\Http\Client\Factory globalRequestMiddleware(callable $middleware)
 * @method static \Illuminate\Http\Client\Factory globalResponseMiddleware(callable $middleware)
 * @method static \Illuminate\Http\Client\Factory globalOptions(\Closure|array $options)
 * @method static \GuzzleHttp\Promise\PromiseInterface response(array|string|null $body = null, int $status = 200, array $headers = [])
 * @method static \GuzzleHttp\Promise\PromiseInterface failedConnection(string|null $message = null)
 * @method static \Illuminate\Http\Client\ResponseSequence sequence(array $responses = [])
 * @method static bool preventingStrayRequests()
 * @method static \Illuminate\Http\Client\Factory allowStrayRequests()
 * @method static void recordRequestResponsePair(\Illuminate\Http\Client\Request $request, \Illuminate\Http\Client\Response|null $response)
 * @method static void assertSent(callable $callback)
 * @method static void assertSentInOrder(array $callbacks)
 * @method static void assertNotSent(callable $callback)
 * @method static void assertNothingSent()
 * @method static void assertSentCount(int $count)
 * @method static void assertSequencesAreEmpty()
 * @method static \Illuminate\Support\Collection recorded(callable $callback = null)
 * @method static \Illuminate\Http\Client\PendingRequest createPendingRequest()
 * @method static \Illuminate\Contracts\Events\Dispatcher|null getDispatcher()
 * @method static array getGlobalMiddleware()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static mixed macroCall(string $method, array $parameters)
 * @method static \Illuminate\Http\Client\PendingRequest baseUrl(string $url)
 * @method static \Illuminate\Http\Client\PendingRequest withBody(\Psr\Http\Message\StreamInterface|string $content, string $contentType = 'application/json')
 * @method static \Illuminate\Http\Client\PendingRequest asJson()
 * @method static \Illuminate\Http\Client\PendingRequest asForm()
 * @method static \Illuminate\Http\Client\PendingRequest attach(string|array $name, string|resource $contents = '', string|null $filename = null, array $headers = [])
 * @method static \Illuminate\Http\Client\PendingRequest asMultipart()
 * @method static \Illuminate\Http\Client\PendingRequest bodyFormat(string $format)
 * @method static \Illuminate\Http\Client\PendingRequest withQueryParameters(array $parameters)
 * @method static \Illuminate\Http\Client\PendingRequest contentType(string $contentType)
 * @method static \Illuminate\Http\Client\PendingRequest acceptJson()
 * @method static \Illuminate\Http\Client\PendingRequest accept(string $contentType)
 * @method static \Illuminate\Http\Client\PendingRequest withHeaders(array $headers)
 * @method static \Illuminate\Http\Client\PendingRequest withHeader(string $name, mixed $value)
 * @method static \Illuminate\Http\Client\PendingRequest replaceHeaders(array $headers)
 * @method static \Illuminate\Http\Client\PendingRequest withBasicAuth(string $username, string $password)
 * @method static \Illuminate\Http\Client\PendingRequest withDigestAuth(string $username, string $password)
 * @method static \Illuminate\Http\Client\PendingRequest withToken(string $token, string $type = 'Bearer')
 * @method static \Illuminate\Http\Client\PendingRequest withUserAgent(string|bool $userAgent)
 * @method static \Illuminate\Http\Client\PendingRequest withUrlParameters(array $parameters = [])
 * @method static \Illuminate\Http\Client\PendingRequest withCookies(array $cookies, string $domain)
 * @method static \Illuminate\Http\Client\PendingRequest maxRedirects(int $max)
 * @method static \Illuminate\Http\Client\PendingRequest withoutRedirecting()
 * @method static \Illuminate\Http\Client\PendingRequest withoutVerifying()
 * @method static \Illuminate\Http\Client\PendingRequest sink(string|resource $to)
 * @method static \Illuminate\Http\Client\PendingRequest timeout(int|float $seconds)
 * @method static \Illuminate\Http\Client\PendingRequest connectTimeout(int|float $seconds)
 * @method static \Illuminate\Http\Client\PendingRequest retry(array|int $times, \Closure|int $sleepMilliseconds = 0, callable|null $when = null, bool $throw = true)
 * @method static \Illuminate\Http\Client\PendingRequest withOptions(array $options)
 * @method static \Illuminate\Http\Client\PendingRequest withMiddleware(callable $middleware)
 * @method static \Illuminate\Http\Client\PendingRequest withRequestMiddleware(callable $middleware)
 * @method static \Illuminate\Http\Client\PendingRequest withResponseMiddleware(callable $middleware)
 * @method static \Illuminate\Http\Client\PendingRequest beforeSending(callable $callback)
 * @method static \Illuminate\Http\Client\PendingRequest throw(callable|null $callback = null)
 * @method static \Illuminate\Http\Client\PendingRequest throwIf(callable|bool $condition)
 * @method static \Illuminate\Http\Client\PendingRequest throwUnless(callable|bool $condition)
 * @method static \Illuminate\Http\Client\PendingRequest dump()
 * @method static \Illuminate\Http\Client\PendingRequest dd()
 * @method static \Illuminate\Http\Client\Response get(string $url, array|string|null $query = null)
 * @method static \Illuminate\Http\Client\Response head(string $url, array|string|null $query = null)
 * @method static \Illuminate\Http\Client\Response post(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response patch(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response put(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response delete(string $url, array $data = [])
 * @method static array pool(callable $callback)
 * @method static \Illuminate\Http\Client\Response send(string $method, string $url, array $options = [])
 * @method static \GuzzleHttp\Client buildClient()
 * @method static \GuzzleHttp\Client createClient(\GuzzleHttp\HandlerStack $handlerStack)
 * @method static \GuzzleHttp\HandlerStack buildHandlerStack()
 * @method static \GuzzleHttp\HandlerStack pushHandlers( $handlerStack)
 * @method static \Closure buildBeforeSendingHandler()
 * @method static \Closure buildRecorderHandler()
 * @method static \Closure buildStubHandler()
 * @method static \GuzzleHttp\Psr7\RequestInterface runBeforeSendingCallbacks( $request, array $options)
 * @method static array mergeOptions( ...$options)
 * @method static \Illuminate\Http\Client\PendingRequest stub(callable $callback)
 * @method static \Illuminate\Http\Client\PendingRequest async(bool $async = true)
 * @method static \GuzzleHttp\Promise\PromiseInterface|null getPromise()
 * @method static \Illuminate\Http\Client\PendingRequest setClient(\GuzzleHttp\Client $client)
 * @method static \Illuminate\Http\Client\PendingRequest setHandler(callable $handler)
 * @method static array getOptions()
 * @method static \Illuminate\Http\Client\PendingRequest|mixed when(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \Illuminate\Http\Client\PendingRequest|mixed unless(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
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
            static::$config = ConfigHelper::get('app', []);
        }

        $factory = Container::get(Factory::class);

        static::bootMacros($factory);

        return $factory;
    }

    public static function __callStatic($name, $arguments)
    {
        $factory = static::instance();

        // 直接调用这些方法会直接调用 PendingRequest 中的方法返回 Response，因此需要手动 attach
        if (in_array($name, [
            'delete', 'get', 'head', 'patch', 'post', 'put', 'send',
        ])) {
            $http = static::newPendingRequest($factory);
            static::attachExtension($http);
            return $http->{$name}(...$arguments);
        }

        // pool 方法的 callable 中会直接调用 PendingRequest 的方法返回 Response，所以需要手动 attach
        if ($name === 'pool') {
            // TODO 目前暂未想好如何自动 attach
        }

        // 其他返回 PendingRequest 的情况
        $result = $factory->{$name}(...$arguments);
        if ($result instanceof PendingRequest) {
            static::attachExtension($result);
            return $result;
        }

        // 返回非 PendingRequest 的情况
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
     * @param PendingRequest $http
     */
    private static function attachExtension($http)
    {
        $attachedExtensionOptionKey = '__attached_extension';

        $options = $http->getOptions();
        if (array_key_exists($attachedExtensionOptionKey, $options)) {
            // PendingRequest 不能多次附加 extension
            return;
        }

        if ($logMiddleware = static::getLogMiddleware()) {
            $http->withMiddleware($logMiddleware);
        }
        $options = array_merge(static::getDefaultOptions(), [
            $attachedExtensionOptionKey => 1,
        ]);
        $http->withOptions($options);
    }

    /**
     * @see Factory::newPendingRequest()
     * @param Factory $factory
     * @return PendingRequest
     */
    private static function newPendingRequest(Factory $factory): PendingRequest
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
            if ($customLog instanceof Closure) {
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
