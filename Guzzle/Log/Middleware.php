<?php

namespace WebmanTech\LaravelHttpClient\Guzzle\Log;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 参考：
 * @link https://github.com/bilfeldt/laravel-http-client-logger
 */
class Middleware
{
    /**
     * @var CustomLogInterface
     */
    protected $customLog;

    public function __construct(CustomLogInterface $customLog)
    {
        $this->customLog = $customLog;
    }

    public function __invoke(): callable
    {
        return function (callable $handler): callable {
            return function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
                $start = microtime(true);

                $promise = $handler($request, $options);

                return $promise->then(
                    function (ResponseInterface $response) use ($request, $start) {
                        $sec = microtime(true) - $start;

                        if ($this->customLog->shouldLog($request, $response, $sec)) {
                            $this->customLog->log($request, $response, $sec);
                        }

                        return $response;
                    }
                );
            };
        };
    }
}