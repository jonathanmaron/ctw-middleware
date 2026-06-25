# PHP 8.5.7 Migration — `ctw/ctw-middleware`

- **Branch:** `php85` (cut from `master`)
- **Runtime:** PHP 8.3.31 → **8.5.7**
- **PHPUnit:** 12 → **13.2.1**
- **Status:** ✅ done

## Audit checklist

### `composer` (platform resolution)

- [x] **(fatal) `laminas/laminas-diactoros` 2.x** — the 2.x line caps PHP at
  ~8.3.0, so `composer update -W` could not resolve a 8.5-compatible install.
  - **Fix:** bumped `laminas/laminas-diactoros` `^2` → **`^3.0`** (resolves
    3.8.0), which supports PHP 8.5.

### `middlewares/utils` (vendor deprecations)

- [x] **(deprecation, vendor) `middlewares/utils` v3.3** — 5 "implicitly nullable
  parameter" deprecations surface from the dependency under PHP 8.4+:
  `Dispatcher::run`, `Factory::createUploadedFile` (×3), and
  `CallableHandler::__construct`.
  - **Fix:** bumped `middlewares/utils` `^3.3` → **`^4.0`**; v4 uses explicit
    `?type` declarations, clearing all 5 deprecations.

## composer.json & CI

- [x] **`require.php`** — `^8.3` → **`^8.5`**.
- [x] **`laminas/laminas-diactoros`** — `^2` → **`^3.0`** (PHP 8.5 support;
  resolves 3.8.0).
- [x] **`middlewares/utils`** — `^3.3` → **`^4.0`** (explicit nullable types).
- [x] **`psr/http-message`** — `^1.0` → **`^1.1 || ^2.0`** (required by
  Diactoros 3).
- [x] **`laminas/laminas-servicemanager`** — `^3.12` → **`^3.12 || ^4.5`**.
  Unused-but-declared dep; widened so downstream `ctw/ctw-pagecache`'s
  laminas-cache 4.3 can resolve servicemanager `^4.5`.
- [x] **`phpunit/phpunit`** — `^12.0` → **`^13.0`** (installs 13.2.1).
- [x] **`ctw/ctw-qa`** — pinned to **`dev-php85`** (inherits the shared PHPStan
  `reportUnmatchedIgnoredErrors: false` fix).
- [x] **`.github/workflows/tests.yml`** — CI matrix pinned to PHP **`8.5`** only.

## Final audit (PHP 8.5.7)

- [x] **`php -v`** — PHP **8.5.7** (cli).
- [x] **`composer update -W`** — clean; Diactoros 3 / utils 4 resolve under PHP
  8.5 with no platform conflict.
- [x] **PHPUnit** — **5 tests, 10 assertions**, no issues (PHPUnit 13.2.1); no
  vendor deprecations remain.
- [x] **PHPStan** — `[OK] No errors` (level max).

```bash
php -v                                  # PHP 8.5.7
composer update -W                      # clean
php vendor/bin/phpunit --no-coverage    # OK (5 tests, 10 assertions)
composer phpstan                        # No issues found
```
