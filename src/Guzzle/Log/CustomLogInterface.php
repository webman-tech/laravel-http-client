<?php

namespace WebmanTech\LaravelHttpClient\Guzzle\Log;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface CustomLogInterface
{
    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param float $sec
     * @return bool
     */
    public function shouldLog(RequestInterface $request, ResponseInterface $response, float $sec): bool;

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param float $sec
     * @return void
     */
    public function log(RequestInterface $request, ResponseInterface $response, float $sec): void;
}