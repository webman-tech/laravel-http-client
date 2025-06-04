# Deprecated 

使用 [webman-tech/laravel-http](https://github.com/webman-tech/laravel-monorepo) 代替


# webman-tech/laravel-http-client

Laravel [illuminate/http](https://packagist.org/packages/illuminate/http) 中的 HttpClient for webman

## 介绍

站在巨人（laravel）的肩膀上使 http 请求使用更加*可靠*和*便捷*

所有方法和配置与 laravel 几乎一模一样，因此使用方式完全参考 [Laravel文档](https://laravel.com/docs/8.x/http-client) 即可

## 安装

```bash
composer require webman-tech/laravel-http-client
```

## 使用

所有 API 同 laravel，以下仅对有些特殊的操作做说明

### Facade 入口

使用 `WebmanTech\LaravelHttpClient\Facades\Http` 代替 `Illuminate\Support\Facades\Http`

### 请求日志

配置文件 `config/plugin/webman-tech/laravel-http-client/app.php` 中的 `log` 栏目可以配置日志相关

默认未启用

支持配置各种情况日志：

- 仅记录响应状态码为 2xx/3xx/4xx/5xx 类型的日志
- 仅记录慢请求
- 不同响应码记录不同 log level
- 不同响应码记录不同 log channel
- 替换请求日志中的敏感信息
- 自动截取部分请求或响应的 body，防止日志过大
- 自动忽略请求或相应是 file 的，防止日志过大

### 默认的 guzzle options 配置

配置文件 `config/plugin/webman-tech/laravel-http-client/app.php` 中的 `guzzle` 栏目可以配置 guzzle 的默认配置

会在每次发送请求时使用该默认值

### 快速定义与简化 api 调用

如果对同一个站点的接口请求比较多，建议通过 `macros` 来预定义一些接口的请求信息（比如 baseUrl、Headers 等）

配置文件 `config/plugin/webman-tech/laravel-http-client/app.php` 中的 `macros` 栏目中

举例：

config

```php
return [
    // 其他配置省略
    'macros' => [
        'httpbin' => function() {
            return Http::baseUrl('https://httpbin.org')
                ->asJson();
        }
    ],
];
```

使用

```php
$response = \WebmanTech\LaravelHttpClient\Facades\Http::httpbin()->get('get', ['abc' => 'xyz']);
```

#### 建议

为了 macros 的代码提示，建议新建一个 `support/facade/Http` 继承自 `WebmanTech\LaravelHttpClient\Facades\Http`，然后顶部添加注释用于代码提示

例如：

```php
<?php

namespace support\facade;

use Illuminate\Http\Client\PendingRequest;

/**
 * @method static PendingRequest httpbin()
 */
class Http extends \WebmanTech\LaravelHttpClient\Facades\Http
{
    public static function getAllMacros(): array
    {
        return [
            'httpbin' => function() {
                return Http::baseUrl('https://httpbin.org')
                    ->asJson();
            }
        ];
    }
}
```

```php
$response = \support\facade\Http::httpbin()->get('get', ['abc' => 'xyz']);
```
