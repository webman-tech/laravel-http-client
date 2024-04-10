<?php

namespace WebmanTech\LaravelHttpClient\Guzzle\Log\Formatter;

use GuzzleHttp\Psr7\Message;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class PsrMessageFormatter implements MessageFormatterInterface
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

        return "Time {$sec}sec\r\n"
            . "Request\r\n"
            . $this->formatMessage($request, $this->config['request_body_limit_size']) . "\r\n"
            . "Response\r\n"
            . $this->formatMessage($response, $this->config['response_body_limit_size']);
    }

    private function formatMessage(MessageInterface $message, int $limit): string
    {
        $str = Message::toString($message);
        if ($limit > strlen($str)) {
            return $str;
        }
        $str = explode("\r\n\r\n", $str)[0];
        $str .= "\r\n\r\n" . Message::bodySummary($message, $limit);

        return $str;
    }
}
