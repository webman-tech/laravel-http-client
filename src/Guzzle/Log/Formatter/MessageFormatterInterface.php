<?php

namespace WebmanTech\LaravelHttpClient\Guzzle\Log\Formatter;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface MessageFormatterInterface
{
    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param float $sec
     * @return string
     */
    public function format(RequestInterface $request, ResponseInterface $response, float $sec): string;
}
