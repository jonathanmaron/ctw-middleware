# CHANGELOG

## Unreleased

Internal modernization for PHP 8.4/8.5. No public API or observable behavior changed.

* Rewrote `AbstractMiddleware::containsHtml()` to use the PHP 8.4 `array_any()` function with `str_contains()`, replacing the nested `foreach` loops and `strpos()`/`is_int()` checks.
* Added the PHP 8.5 `#[\NoDiscard]` attribute to `AbstractMiddleware::containsHtml()` and `AbstractMiddleware::getSuffixStatistics()`, since both are pure methods whose return value must be used.

## 3.0.0 - 2022-07-07

* Added support for PHP 8.1.
* Improved code to `phpstan` level `max`.
* Minor internal refactoring.
* Removed support for PHP 7.2.
