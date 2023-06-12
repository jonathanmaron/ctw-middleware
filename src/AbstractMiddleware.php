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
    protected const   HTML_MIME_TYPES
        = [
            'text/html',
            'application/xhtml',
        ];

    protected function containsHtml(ResponseInterface $response): bool
    {
        $header = $response->getHeader('Content-Type');

        if ([] === $header) {
            return false;
        }

        foreach (self::HTML_MIME_TYPES as $needle) {
            foreach ($header as $haystack) {
                $pos = strpos($haystack, $needle);
                if (is_int($pos)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Return an array of statistics for use in the suffix added to the HTML
     *
     *
     */
    protected function getSuffixStatistics(string $original, string $minified): array
    {
        $in      = mb_strlen($original);
        $out     = mb_strlen($minified);
        $percent = 100 * ($out / $in);
        $diff    = 100 - $percent;

        return [$in, $out, $diff];
    }
}
