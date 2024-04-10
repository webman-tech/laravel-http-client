<?php

namespace WebmanTech\LaravelHttpClient\Guzzle\Log\Formatter;

use GuzzleHttp\Psr7\Message;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class JsonMessageFormatter implements MessageFormatterInterface
{
    use MessageFormatterTrait;

    private $config = [
        'request_body_limit_size' => 300, // request body 记录最大长度
        'response_body_limit_size' => 300, // response body 记录最大长度
        'request_body_skip_file' => true, // 提交文件时，跳过 body 记录
        'response_body_skip_file' => true, // 提交文件时，跳过 body 记录
    ];

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * @inheritDoc
     */
    public function format(RequestInterface $request, ResponseInterface $response, float $sec): string
    {
        if ($this->config['request_body_skip_file'] && $this->isRequestHasFile($request)) {
            $this->config['request_body_limit_size'] = 0;
        }
        if ($this->config['response_body_skip_file'] && $this->isResponseHasFile($response)) {
            $this->config['response_body_limit_size'] = 0;
        }

        return json_encode([
            'time' => $sec,
            'request' => [
                'method' => $request->getMethod(),
                'url' => $request->getUri()->__toString(),
                'headers' => $this->formatHeaders($request->getHeaders()),
                'body' => Message::bodySummary($request, $this->config['request_body_limit_size']),
            ],
            'response' => [
                'status_code' => $response->getStatusCode(),
                'headers' => $this->formatHeaders($response->getHeaders()),
                'body' => Message::bodySummary($response, $this->config['response_body_limit_size']),
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function formatHeaders(array $headers): array
    {
        $data = [];
        foreach ($headers as $name => $values) {
            $data[$name] = implode(', ', $values);
        }
        return $data;
    }
}
