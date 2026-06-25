# PHP 8.5.7 Upgrade — `ctw/ctw-middleware`

- **Branch:** `php85` (cut from `master`)
- **Runtime:** PHP 8.3.31 → **8.5.7**
- **Date:** 2026-06-25

This is a **TODO list** of the changes required for this package to run cleanly
under PHP 8.5.7. Nothing here has been fixed yet — the fixes happen in a second
step. Boxes are intentionally left unchecked.

> **This is the base middleware package.** Every `ctw/ctw-middleware-*` package
> requires `ctw/ctw-middleware ^4.0`. The `laminas-diactoros` blocker below is
> therefore the root cause of the `composer update` failures reported across all
> of those packages — they cannot be unblocked until a new `ctw/ctw-middleware`
> release (with the diactoros bump) is tagged and published to the Satis repo.

Detection commands used:

```bash
composer update -W
php vendor/bin/phpunit --no-coverage --display-deprecations --display-warnings --display-notices --display-errors
composer rector      # rector --dry-run
composer phpstan
```

---

## 1. `composer update -W` — ❌ FAILS (hard blocker)

```
Problem 1
  - Root composer.json requires laminas/laminas-diactoros ^2.11
  - laminas/laminas-diactoros[2.11 ... 2.26] require php ~8.0 || ~8.1 || ~8.2 || ~8.3
    -> your php version (8.5.7) does not satisfy that requirement.
```

**Root cause:** the entire `laminas/laminas-diactoros` **2.x** line caps PHP at
`~8.3.0`. There is no 2.x release that supports PHP 8.4/8.5, so under PHP 8.5.7
the requirement cannot be resolved and `composer update` aborts (nothing is
installed/updated).

- [ ] **`composer.json`** — bump `laminas/laminas-diactoros` from `^2.11` to
  **`^3.0`** (Diactoros 3.x requires PHP ^8.1 and supports 8.4/8.5).
  - [ ] After the bump, re-run `composer update -W` and resolve any *secondary*
    conflicts that only surface once Diactoros is unblocked (likely candidates:
    `laminas/laminas-servicemanager ^3.12` — confirm a PHP 8.5-compatible
    release is selected; `middlewares/utils ^3.3`; `psr/http-message ^1.0` →
    Diactoros 3 wants `psr/http-message ^1.1 || ^2.0`, so this constraint very
    likely needs widening to `^1.1 || ^2.0`).
  - [ ] Tag & publish a new release (e.g. `4.1.0` / `5.0.0`) to the Satis repo so
    the downstream `ctw/ctw-middleware-*` packages can update.

> Because `composer update` aborts, the runtime findings in §2 were captured
> against the **existing** (master) lockfile and may shift once the dependency
> tree is actually updated.

---

## 2. PHP 8.5 runtime deprecations

All five originate in the **third-party** `middlewares/utils` dependency (not in
this package's own source). They are the "implicitly nullable parameter"
deprecation (deprecated since PHP 8.4, still emitted under 8.5):

| Location | Method / parameter |
| --- | --- |
| `vendor/middlewares/utils/src/Dispatcher.php:21` | `Dispatcher::run()` `$request` |
| `vendor/middlewares/utils/src/Factory.php:88` | `Factory::createUploadedFile()` `$size` |
| `vendor/middlewares/utils/src/Factory.php:90` | `Factory::createUploadedFile()` `$filename` |
| `vendor/middlewares/utils/src/Factory.php:91` | `Factory::createUploadedFile()` `$mediaType` |
| `vendor/middlewares/utils/src/CallableHandler.php:25` | `CallableHandler::__construct()` `$responseFactory` |

- [ ] These are **not** fixable in this repo's `src/`. Resolution path: after the
  Diactoros bump unblocks `composer update`, verify whether a newer
  `middlewares/utils` release (within or above `^3.3`) clears them. If the latest
  release still emits them, an upstream fix / replacement is required (track
  separately). No first-party source changes are needed in `ctw/ctw-middleware`
  itself.

---

## 3. QA tooling issues

- [ ] **PHPStan unmatched ignore pattern** — `Ignored error pattern
  missingType.generics / missingType.iterableValue was not matched`. Originates
  in the shared `config/phpstan/common.neon` shipped by **`ctw/ctw-qa`**; fix it
  centrally there (`ctw-qa/dev-php85/UPDATE.md` §3). PHPStan currently reports
  **4 errors**, all of which are these unmatched-pattern errors (no real
  first-party type errors).

---

## 4. Notes (non-blocking, not PHP 8.5 specific)

- Run the suite locally with `--no-coverage` (no Xdebug/PCOV installed here,
  otherwise "No tests executed!"). Not a PHP 8.5 regression.

---

## 5. Verification snapshot (current state on `php85`)

| Check | Result |
| --- | --- |
| `composer update -W` | ❌ fails — `laminas-diactoros` 2.x vs PHP 8.5 (§1) |
| PHPUnit (`--no-coverage`, stale deps) | 5 tests, 10 assertions, **5 deprecations** (all `middlewares/utils`, §2) |
| Rector (dry-run) | ✅ no changes proposed |
| PHPStan | ❌ 4 errors (all shared unmatched-ignore, §3) |

**Order of work:** §1 first (unblocks everything), then re-evaluate §2, then §3
(in `ctw-qa`). Once §1 lands and is published, the downstream `*-middleware-*`
packages can be updated.
