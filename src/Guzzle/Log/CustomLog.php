<?php

namespace WebmanTech\LaravelHttpClient\Guzzle\Log;

use GuzzleHttp\Psr7\Message;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use support\Log;

class CustomLog implements CustomLogInterface
{
    protected $config = [
        'filter_all' => false,
        'filter_2xx' => true,
        'filter_3xx' => true,
        'filter_4xx' => true,
        'filter_5xx' => true,
        'filter_slow' => 1.5,
        'log_channel' => 'default',
        'log_level_all' => null,
        'log_level_2xx' => 'debug',
        'log_level_3xx' => 'info',
        'log_level_4xx' => 'warning',
        'log_level_5xx' => 'error',
        'log_replacer' => [],
    ];

    public function __construct(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * @inheritDoc
     */
    public function shouldLog(RequestInterface $request, ResponseInterface $response, float $sec): bool
    {
        if ($this->config['filter_all']) {
            return true;
        }

        $codeType = substr($response->getStatusCode(), 0, 1);
        if ($this->config["filter_{$codeType}xx"]) {
            return true;
        }

        if ($this->config['filter_slow'] < $sec) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function log(RequestInterface $request, ResponseInterface $response, float $sec): void
    {
        $level = $this->config['log_level_all'];
        if (!$level) {
            $codeType = substr($response->getStatusCode(), 0, 1);
            $level = $this->config["log_level_{$codeType}xx"] ?? 'info';
        }
        $message = $this->getMessage($request, $response, $sec);
        Log::channel($this->config['log_channel'])->log($level, $message);
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param float $sec
     * @return string
     */
    protected function getMessage(RequestInterface $request, ResponseInterface $response, float $sec): string
    {
        return "Time {$sec}sec\r\n"
            . "Request\r\n"
            . strtr(Message::toString($request), $this->config['log_replacer']) . "\r\n"
            . "Response\r\n"
            . strtr(Message::toString($response), $this->config['log_replacer']);
    }
}