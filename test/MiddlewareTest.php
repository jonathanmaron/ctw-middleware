<?php
declare(strict_types=1);

namespace CtwTest\Middleware;

use Ctw\Middleware\AbstractMiddleware;
use DivisionByZeroError;
use Middlewares\Utils\Dispatcher;
use Psr\Http\Message\ResponseInterface as ResponseIface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;

final class MiddlewareTest extends AbstractCase
{
    /**
     * Test that the class declares the HTML_SUFFIX and HTML_MIME_TYPES constants with the expected types when inspected via reflection.
     */
    public function testClassDeclaresHtmlSuffixAndHtmlMimeTypesConstants(): void
    {
        $reflectionClass = new ReflectionClass($this->getInstance());

        $constants = $reflectionClass->getConstants();

        self::assertArrayHasKey('HTML_SUFFIX', $constants);
        self::assertArrayHasKey('HTML_MIME_TYPES', $constants);

        self::assertIsString($constants['HTML_SUFFIX']);
        self::assertIsArray($constants['HTML_MIME_TYPES']);
    }

    /**
     * Test that containsHtml() returns true when the Content-Type is text/html.
     */
    public function testContainsHtmlReturnsTrueWhenContentTypeIsTextHtml(): void
    {
        $stack    = [$middleware = $this->getInstance()];
        $response = Dispatcher::run($stack);
        $response = $response->withHeader('Content-Type', 'text/html');

        // @phpstan-ignore-next-line
        self::assertTrue($middleware->publicContainsHtml($response));
    }

    /**
     * Test that containsHtml() returns true when the Content-Type is application/xhtml.
     */
    public function testContainsHtmlReturnsTrueWhenContentTypeIsApplicationXhtml(): void
    {
        $stack    = [$middleware = $this->getInstance()];
        $response = Dispatcher::run($stack);
        $response = $response->withHeader('Content-Type', 'application/xhtml');

        // @phpstan-ignore-next-line
        self::assertTrue($middleware->publicContainsHtml($response));
    }

    /**
     * Test that containsHtml() returns false when the Content-Type is application/json.
     */
    public function testContainsHtmlReturnsFalseWhenContentTypeIsApplicationJson(): void
    {
        $stack    = [$middleware = $this->getInstance()];
        $response = Dispatcher::run($stack);
        $response = $response->withHeader('Content-Type', 'application/json');

        // @phpstan-ignore-next-line
        self::assertFalse($middleware->publicContainsHtml($response));
    }

    /**
     * Test that containsHtml() returns false when no Content-Type header is present.
     */
    public function testContainsHtmlReturnsFalseWhenContentTypeHeaderIsAbsent(): void
    {
        $stack    = [$middleware = $this->getInstance()];
        $response = Dispatcher::run($stack);

        // @phpstan-ignore-next-line
        self::assertFalse($middleware->publicContainsHtml($response));
    }

    /**
     * Test that getSuffixStatistics() returns the input length, output length and savings percentage when the minified output is smaller than the original.
     */
    public function testGetSuffixStatisticsReturnsInputOutputLengthsAndSavingsPercentage(): void
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
        /** @var array{int, int, float} $array */

        self::assertSame(70, $array[0]);
        self::assertSame(49, $array[1]);
        self::assertSame(30.0, $array[2]);
    }

    /**
     * Test that getSuffixStatistics() reports a negative saving when the minified output is larger than the original.
     */
    public function testGetSuffixStatisticsReportsNegativeSavingWhenMinifiedIsLargerThanOriginal(): void
    {
        $middleware = $this->getInstance();

        $original = '<p>x</p>';
        $minified = '<p>xxxxx</p>';

        // @phpstan-ignore-next-line
        $array = $middleware->publicGetSuffixStatistics($original, $minified);
        /** @var array{int, int, float} $array */

        self::assertSame(8, $array[0]);
        self::assertSame(12, $array[1]);
        self::assertSame(-50.0, $array[2]);
    }

    /**
     * Test that getSuffixStatistics() throws a DivisionByZeroError when the original is empty.
     */
    public function testGetSuffixStatisticsThrowsDivisionByZeroErrorWhenOriginalIsEmpty(): void
    {
        $middleware = $this->getInstance();

        $this->expectException(DivisionByZeroError::class);

        // @phpstan-ignore-next-line
        $middleware->publicGetSuffixStatistics('', '');
    }

    /**
     * Test that containsHtml() returns true when the Content-Type also carries a charset parameter.
     */
    public function testContainsHtmlReturnsTrueWhenContentTypeIncludesCharsetParameter(): void
    {
        $stack    = [$middleware = $this->getInstance()];
        $response = Dispatcher::run($stack);
        $response = $response->withHeader('Content-Type', 'text/html; charset=utf-8');

        // @phpstan-ignore-next-line
        self::assertTrue($middleware->publicContainsHtml($response));
    }

    /**
     * Test that containsHtml() returns true when an HTML MIME type appears in a later Content-Type header value.
     */
    public function testContainsHtmlReturnsTrueWhenHtmlMimeTypeAppearsInLaterHeaderValue(): void
    {
        $stack    = [$middleware = $this->getInstance()];
        $response = Dispatcher::run($stack);
        $response = $response
            ->withHeader('Content-Type', 'application/json')
            ->withAddedHeader('Content-Type', 'text/html');

        // @phpstan-ignore-next-line
        self::assertTrue($middleware->publicContainsHtml($response));
    }

    /**
     * Test that getSuffixStatistics() reports a zero saving when the minified output equals the original.
     */
    public function testGetSuffixStatisticsReportsZeroSavingWhenMinifiedEqualsOriginal(): void
    {
        $middleware = $this->getInstance();

        $html = '<p>identical</p>';

        // @phpstan-ignore-next-line
        $array = $middleware->publicGetSuffixStatistics($html, $html);
        /** @var array{int, int, int} $array */

        self::assertSame(16, $array[0]);
        self::assertSame(16, $array[1]);
        // When the percentage is a whole number PHP's division yields an int, so the saving is int 0 here.
        self::assertSame(0, $array[2]);
    }

    /**
     * Test that getSuffixStatistics() reports a full saving when the minified output is empty but the original is not.
     */
    public function testGetSuffixStatisticsReportsFullSavingWhenMinifiedIsEmpty(): void
    {
        $middleware = $this->getInstance();

        // @phpstan-ignore-next-line
        $array = $middleware->publicGetSuffixStatistics('<p>x</p>', '');
        /** @var array{int, int, int} $array */

        self::assertSame(8, $array[0]);
        self::assertSame(0, $array[1]);
        // When the percentage is a whole number PHP's division yields an int, so the full saving is int 100 here.
        self::assertSame(100, $array[2]);
    }

    /**
     * Test that getSuffixStatistics() counts multibyte characters rather than raw bytes when measuring lengths.
     */
    public function testGetSuffixStatisticsCountsMultibyteCharactersNotBytes(): void
    {
        $middleware = $this->getInstance();

        // "éà" is two characters but four bytes in UTF-8; "x" is one character.
        $original = 'éà';
        $minified = 'x';

        // @phpstan-ignore-next-line
        $array = $middleware->publicGetSuffixStatistics($original, $minified);
        /** @var array{int, int, float} $array */

        self::assertSame(2, $array[0]);
        self::assertSame(1, $array[1]);
        self::assertSame(50.0, $array[2]);
    }

    private function getInstance(): AbstractMiddleware
    {
        return new class() extends AbstractMiddleware {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseIface
            {
                return $handler->handle($request);
            }

            public function publicContainsHtml(ResponseIface $response): bool
            {
                return $this->containsHtml($response);
            }

            /**
             * @return array{int, int, float}
             */
            public function publicGetSuffixStatistics(string $original, string $minified): array
            {
                return $this->getSuffixStatistics($original, $minified);
            }
        };
    }
}
