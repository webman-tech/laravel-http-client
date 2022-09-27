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
        'log_channel_2xx' => null,
        'log_channel_3xx' => null,
        'log_channel_4xx' => null,
        'log_channel_5xx' => null,
        'log_channel_slow' => null,
        'log_level_all' => null,
        'log_level_2xx' => 'debug',
        'log_level_3xx' => 'info',
        'log_level_4xx' => 'warning',
        'log_level_5xx' => 'error',
        'log_level_slow' => 'warning',
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

        $codeType = $this->getStatusCodeType($response->getStatusCode());
        if ($this->config["filter_{$codeType}xx"] ?? null) {
            return true;
        }

        if ($this->isRequestSlow($sec)) {
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
        $channel = $this->config['log_channel'];
        if (!$level || !$channel) {
            $codeType = $this->getStatusCodeType($response->getStatusCode());
            $isRequestSlow = $this->isRequestSlow($sec);
            if (!$level) {
                $level = $isRequestSlow ? $this->config['log_level_slow'] : ($this->config["log_level_{$codeType}xx"] ?? 'info');
            }
            if (!$channel) {
                $channel = $isRequestSlow ? $this->config['log_channel_slow'] : ($this->config["log_channel_{$codeType}xx"] ?? 'default');
            }
        }
        $message = $this->getMessage($request, $response, $sec);
        Log::channel($channel)->log($level, $message);
    }

    protected function getStatusCodeType(int $statusCode): int
    {
        return substr($statusCode, 0, 1);
    }

    protected function isRequestSlow(float $sec): bool
    {
        return $this->config['filter_slow'] < $sec;
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