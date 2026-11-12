# Changelog

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
