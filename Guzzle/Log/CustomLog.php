<?php

namespace WebmanTech\LaravelHttpClient\Guzzle\Log;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use support\Log;
use WebmanTech\LaravelHttpClient\Guzzle\Log\Formatter\MessageFormatterInterface;
use WebmanTech\LaravelHttpClient\Guzzle\Log\Formatter\PsrMessageFormatter;

class CustomLog implements CustomLogInterface
{
    protected $config = [
        // 定义哪些被记录日志
        'filter_all' => false, // 全部请求
        'filter_2xx' => true, // 响应 code 为 2xx 的
        'filter_3xx' => true, // 响应 code 为 3xx 的
        'filter_4xx' => true, // 响应 code 为 4xx 的
        'filter_5xx' => true, // 响应 code 为 5xx 的
        'filter_slow' => 1.5, // 请求时长超过这个值的，单位秒
        // 日志通道
        'log_channel' => 'default', // 默认的
        'log_channel_2xx' => null,
        'log_channel_3xx' => null,
        'log_channel_4xx' => null,
        'log_channel_5xx' => null,
        'log_channel_slow' => null,
        // 日志等级
        'log_level_all' => null,
        'log_level_2xx' => 'debug',
        'log_level_3xx' => 'info',
        'log_level_4xx' => 'warning',
        'log_level_5xx' => 'error',
        'log_level_slow' => 'warning',
        // 日志的格式
        'log_formatter' => PsrMessageFormatter::class,
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
        $codeType = $this->getStatusCodeType($response->getStatusCode());
        $isRequestSlow = $this->isRequestSlow($sec);

        $level = $channel = null;
        if ($isRequestSlow) {
            $level = $this->config['log_level_slow'];
            $channel = $this->config['log_channel_slow'];
        }
        if (!$level) {
            $level = $this->config["log_level_{$codeType}xx"] ?? null;
        }
        if (!$channel) {
            $channel = $this->config["log_channel_{$codeType}xx"] ?? null;
        }
        if (!$level) {
            $level = $this->config['log_level_all'] ?? 'info';
        }
        if (!$channel) {
            $channel = $this->config['log_channel'] ?? 'default';
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
        $formatter = $this->config['log_formatter'];
        if ($formatter instanceof \Closure) {
            $formatter = call_user_func($formatter, $request, $response, $sec);
        }
        if (is_string($formatter)) {
            $formatter = new $formatter();
        }
        if (!$formatter instanceof MessageFormatterInterface) {
            throw new \InvalidArgumentException('log_formatter config error');
        }
        return $formatter->format($request, $response, $sec);
    }
}
