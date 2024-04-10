<?php

namespace WebmanTech\LaravelHttpClient\Guzzle\Log\Formatter;

use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

trait MessageFormatterTrait
{
    private function isRequestHasFile(RequestInterface $request): bool
    {
        if ($request->hasHeader('Content-Disposition')) {
            return true;
        }
        $contentType = implode(',', $request->getHeader('Content-Type'));
        return Str::containsAll($contentType, [
            'multipart/form-data',
            'boundary=',
        ]);
    }

    private function isResponseHasFile(ResponseInterface $response): bool
    {
        if ($response->hasHeader('Content-Disposition')) {
            return true;
        }
        $contentType = implode(',', $response->getHeader('Content-Type'));
        if (Str::contains($contentType, 'application/') && !Str::contains($contentType, ['/json', '/xml'])) {
            return true;
        }
        if (Str::contains($contentType, 'text/csv')) {
            return true;
        }

        return false;
    }
}
