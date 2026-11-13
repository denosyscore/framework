# Changelog

## 0.19.0 – 2026-11-13

- Released with the framework's 0.19 line. Requires its DenoSys dependencies
  at `^0.19`; no other changes.

## 0.18.0 – 2026-11-13

- Released with the framework's 0.18 line. Requires its DenoSys dependencies
  at `^0.18`; no other changes.

## 0.17.0 – 2026-11-12

- Released with the framework's 0.17 line. Requires its DenoSys dependencies
  at `^0.17`; no other changes.

## 0.16.0 – 2026-11-12

- Released with the framework's 0.16 line; no changes to this package.

## 0.15.0 – 2026-11-12

- Released with the framework's 0.15 line; no changes to this package.

## 0.14.0 – 2026-11-12

- Released with the framework's 0.14 line; no changes to this package.

## 0.13.0 – 2026-11-12

- Released with the framework's 0.13 line; no changes to this package.

## 0.12.0 – 2026-11-12

- Released with the framework's 0.12 line; no changes to this package.

## 0.11.1 – 2026-11-12

### Fixed

- Requirements on other DenoSys packages are `^0.11`. In 0.11.0 they were
  still `^0.10`, which for a 0.x version allows only 0.10 releases, so 0.11.0
  packages resolved their siblings to 0.10 or could not be installed. Use
  0.11.1 instead of 0.11.0.

## 0.11.0 – 2026-11-12

- **Breaking:** `Denosys\RateLimiter\RateLimiterServiceProvider` moved to the framework as `Denosys\Framework\Providers\RateLimiterServiceProvider`. Packages no longer ship service providers; the framework wires packages together.
- Adds `Middleware\ThrottleRequestsMiddleware` (from http); requires `denosyscore/http`.

## 0.10.0 – 2026-11-12

### Changed

- Released from the [denosyscore/framework](https://github.com/denosyscore/framework)
  monorepo, where this package now lives under `packages/rate-limiter`. All DenoSys
  packages share one version line, so the version moves from v0.2.4 to
  0.10.0. There are no API changes in this release.
- Requirements on other DenoSys packages are `^0.10`.

## 0.2.4 – 2026-11-12

### Fixed

- Require `psr/http-message`, `psr/http-server-handler` and
  `psr/http-server-middleware`, which the throttle middleware implements.

## 0.2.3 – 2026-11-12

### Fixed

- `RateLimiter` accepts any key. Keys are hashed before they reach the cache,
  so keys such as `login:203.0.113.7`, IPv6 addresses and email addresses no
  longer throw `InvalidCacheKeyException` (PSR-16 reserves `{}()/\@:`).
  Counters stored under the old key format are ignored after upgrading, so
  open rate-limit windows start again once.

### Added

- PHPUnit tests, run in CI, and a README quick start that CI runs after
  installing the package on its own.
