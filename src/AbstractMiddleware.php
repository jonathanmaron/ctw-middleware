<?php
declare(strict_types=1);

namespace Ctw\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;

abstract class AbstractMiddleware implements MiddlewareInterface
{
    /**
     * Suffix added to HTML Responses
     * @var string
     */
    protected const HTML_SUFFIX = '<!-- html: in %d b | out %d b | diff %01.4f %% -->';

    /**
     * Responses with these MIME types are HTML Responses
     * @var string[]
     */
    protected const HTML_MIME_TYPES
        = ['text/html', 'application/xhtml'];

    #[\NoDiscard]
    protected function containsHtml(ResponseInterface $response): bool
    {
        $header = $response->getHeader('Content-Type');

        return array_any(
            self::HTML_MIME_TYPES,
            static fn(string $mimeType): bool => array_any(
                $header,
                static fn(string $headerValue): bool => str_contains($headerValue, $mimeType),
            ),
        );
    }

    /**
     * Return an array of statistics for use in the suffix added to the HTML
     * @return array{int, int, float}
     */
    #[\NoDiscard]
    protected function getSuffixStatistics(string $original, string $minified): array
    {
        $in      = mb_strlen($original);
        $out     = mb_strlen($minified);
        $percent = 100 * ($out / $in);
        $diff    = 100 - $percent;

        return [$in, $out, $diff];
    }
}
