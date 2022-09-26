<?php

use WebmanTech\LaravelHttpClient\Facades\Http;

return [
    'enable' => true,
    /**
     * 日志相关
     */
    'log' => [
        /**
         * 日志是否启用，建议启用
         */
        'enable' => true,
        /**
         * 日志的 channel
         */
        'channel' => 'httpClient',
        /**
         * 日志的级别
         */
        'level' => 'info',
        /**
         * 日志格式
         */
        'format' => \GuzzleHttp\MessageFormatter::CLF,
    ],
    /**
     * guzzle 全局的 options
     * @link https://laravel.com/docs/8.x/http-client#guzzle-options
     */
    'guzzle' => [
        'debug' => false,
        'timeout' => 10,
    ],
    /**
     * 扩展 Http 功能，一般可用于快速定义 api 信息
     * @link https://laravel.com/docs/8.x/http-client#macros
     */
    'macros' => [
        /*'httpbin' => function() {
            return Http::baseUrl('https://httpbin.org')
                ->asJson();
        }*/
    ],
];
