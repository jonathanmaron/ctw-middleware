<?php
declare(strict_types=1);

namespace CtwTest\Middleware;

use Ctw\Middleware\AbstractMiddleware;
use Middlewares\Utils\Dispatcher;
use Psr\Http\Message\ResponseInterface as ResponseIface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;

class MiddlewareTest extends AbstractCase
{
    public function testClassConstants(): void
    {
        $reflectionClass = new ReflectionClass($this->getInstance());

        $constants = $reflectionClass->getConstants();

        self::assertArrayHasKey('HTML_SUFFIX', $constants);
        self::assertArrayHasKey('HTML_MIME_TYPES', $constants);

        self::assertIsString($constants['HTML_SUFFIX']);
        self::assertIsArray($constants['HTML_MIME_TYPES']);
    }

    public function testContainsHtmlWithHtmlContentType(): void
    {
        $stack    = [
            $middleware = $this->getInstance(),
        ];
        $response = Dispatcher::run($stack);
        $response = $response->withHeader('Content-Type', 'text/html');

        // @phpstan-ignore-next-line
        self::assertTrue($middleware->publicContainsHtml($response));
    }

    public function testContainsHtmlWithJsonContentType(): void
    {
        $stack    = [
            $middleware = $this->getInstance(),
        ];
        $response = Dispatcher::run($stack);
        $response = $response->withHeader('Content-Type', 'application/json');

        // @phpstan-ignore-next-line
        self::assertFalse($middleware->publicContainsHtml($response));
    }

    public function testContainsHtmlWithNoContentType(): void
    {
        $stack    = [
            $middleware = $this->getInstance(),
        ];
        $response = Dispatcher::run($stack);

        // @phpstan-ignore-next-line
        self::assertFalse($middleware->publicContainsHtml($response));
    }

    public function testGetSuffixStatistics(): void
    {
        $middleware = $this->getInstance();

        $original = <<<EOL
        <ul>
            <li>1</li>
            <li>2</li>
            <li>3</li>
            <li>4</li>
        </ul>
        EOL;
        $minified = '<ul><li>1</li><li>2</li><li>3</li><li>4</li></ul>';

        // @phpstan-ignore-next-line
        $array = $middleware->publicGetSuffixStatistics($original, $minified);

        self::assertSame(70, $array[0]);
        self::assertSame(49, $array[1]);
        self::assertSame(30.0, $array[2]);
    }

    private function getInstance(): AbstractMiddleware
    {
        return new class extends AbstractMiddleware {

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseIface
            {
                return $handler->handle($request);
            }

            public function publicContainsHtml(ResponseIface $response): bool
            {
                return $this->containsHtml($response);
            }

            public function publicGetSuffixStatistics(string $original, string $minified): array
            {
                return $this->getSuffixStatistics($original, $minified);
            }
        };
    }
}
