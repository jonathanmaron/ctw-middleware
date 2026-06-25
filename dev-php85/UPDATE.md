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

## 1. `composer update -W` — ✅ FIXED

**Root cause (was):** the entire `laminas/laminas-diactoros` **2.x** line caps
PHP at `~8.3.0`. No 2.x release supports PHP 8.4/8.5, so under PHP 8.5.7 the
requirement could not be resolved and `composer update` aborted.

**Applied fix (`composer.json`):**

- [x] `laminas/laminas-diactoros` `^2.11` → **`^3.0`** — installs **3.8.0**.
- [x] `psr/http-message` `^1.0` → **`^1.1 || ^2.0`** (required by Diactoros 3) —
  installs **1.1**.
- [x] `middlewares/utils` `^3.3` → **`^4.0`** — installs **4.0.2**. v4 declares
  explicit nullable parameter types, which **clears all five implicitly-nullable
  deprecations** previously listed in §2.
- [x] `laminas/laminas-servicemanager` `^3.12` left as-is — `composer update -W`
  selects PHP 8.5-compatible **3.24.0**.

`composer update -W` now completes cleanly (rc=0). The `php85` branch is
published on Satis as `dev-php85`, so downstream `ctw/ctw-middleware-*` packages
consume this fix by requiring `ctw/ctw-middleware: dev-php85` (re-tag to a stable
release before the upgrade lands on `master`).

- [ ] **Remaining (step 2):** tag & publish a stable release (e.g. `4.1.0` /
  `5.0.0`) so downstream packages can pin a release instead of `dev-php85`.

---

## 2. PHP 8.5 runtime deprecations — ✅ RESOLVED

The five "implicitly nullable parameter" deprecations originated in
**`middlewares/utils` v3.3.0** (`Dispatcher::run()`, `Factory::createUploadedFile()`
×3, `CallableHandler::__construct()`). The **`middlewares/utils ^4.0`** bump in §1
(v4.0.2 uses explicit `?type` parameters) clears all of them.

- [x] Verified: `phpunit --no-coverage` now reports **5 tests, 10 assertions, 0
  deprecations**.

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
| `composer update -W` | ✅ clean (diactoros 3.8.0, middlewares/utils 4.0.2, psr/http-message 1.1) |
| PHPUnit (`--no-coverage`) | ✅ 5 tests, 10 assertions, **0 deprecations** |
| Rector (dry-run) | ✅ no changes proposed |
| PHPStan | ❌ 2 errors (shared unmatched-ignore from `ctw/ctw-qa`, §3) |

Only §3 (the shared PHPStan unmatched-ignore pattern, owned by `ctw/ctw-qa`)
remains; it is a QA-tooling nit, not a runtime issue.
