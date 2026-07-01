# Package "ctw/ctw-middleware"

[![Latest Stable Version](https://poser.pugx.org/ctw/ctw-middleware/v/stable)](https://packagist.org/packages/ctw/ctw-middleware)
[![GitHub Actions](https://github.com/jonathanmaron/ctw-middleware/actions/workflows/ci.yml/badge.svg)](https://github.com/jonathanmaron/ctw-middleware/actions/workflows/ci.yml)

Abstract base class providing shared functionality for the ctw/ctw-middleware-* PSR-15 middleware packages.

## Introduction

### Why This Library Exists

Modern PHP applications built on frameworks like Mezzio leverage PSR-15 middleware for request/response processing. When building multiple middleware components that share common functionality, duplicating code across packages leads to maintenance burden and inconsistencies.

This library provides a centralized `AbstractMiddleware` base class that:

- Implements `Psr\Http\Server\MiddlewareInterface` for PSR-15 compliance
- Provides utility methods for detecting HTML responses via Content-Type headers
- Offers statistics calculation for middleware that transform HTML content (minification, tidying)
- Establishes a consistent foundation for the entire ctw/ctw-middleware-* family

### Problems This Library Solves

1. **Code duplication**: Without a shared base, each middleware would implement its own HTML detection and statistics logic
2. **Inconsistent behavior**: Different implementations of content-type checking could lead to subtle bugs
3. **Maintenance overhead**: Bug fixes or improvements would need to be replicated across multiple packages
4. **Missing PSR compliance**: Ensures all derived middleware properly implements `MiddlewareInterface`

### Where to Use This Library

- **As a dependency**: All `ctw/ctw-middleware-*` packages depend on this library
- **Custom middleware**: Extend `AbstractMiddleware` when building your own HTML-processing middleware
- **HTML response detection**: Use `containsHtml()` to check if a response contains HTML content
- **Transformation statistics**: Use `getSuffixStatistics()` to generate size comparison metrics

### Design Goals

1. **Minimal footprint**: Only essential shared functionality, no bloat
2. **PSR-15 compliant**: Implements the standard middleware interface
3. **Type-safe**: Full `declare(strict_types=1)` support
4. **Extensible**: Abstract class designed to be extended, not instantiated directly
5. **Zero configuration**: Works out of the box with sensible defaults

## Requirements

- PHP 8.3 or higher
- ext-mbstring

## Installation

Install by adding the package as a [Composer](https://getcomposer.org) requirement:

```bash
composer require ctw/ctw-middleware
```

## Usage Examples

### Extending AbstractMiddleware

```php
use Ctw\Middleware\AbstractMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MyCustomMiddleware extends AbstractMiddleware
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $response = $handler->handle($request);

        // Only process HTML responses
        if (!$this->containsHtml($response)) {
            return $response;
        }

        // Your HTML processing logic here
        $original = $response->getBody()->getContents();
        $modified = $this->transformHtml($original);

        // Get statistics for the HTML comment suffix
        [$in, $out, $diff] = $this->getSuffixStatistics($original, $modified);

        // Append statistics comment
        $modified .= PHP_EOL . sprintf(self::HTML_SUFFIX, $in, $out, $diff);

        // Return modified response
        // ...
    }
}
```

### HTML Detection

The `containsHtml()` method checks for `text/html` or `application/xhtml` in the `Content-Type` header:

```php
if ($this->containsHtml($response)) {
    // Process HTML content
}
```

### Transformation Statistics

For middleware that transforms HTML (minification, beautification), use `getSuffixStatistics()`:

```php
[$inputBytes, $outputBytes, $percentReduction] = $this->getSuffixStatistics($original, $transformed);

// Appends comment like: <!-- html: in 15420 b | out 12336 b | diff 20.0000 % -->
```
