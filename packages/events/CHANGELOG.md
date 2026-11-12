# Changelog

## 0.11.1 – 2026-11-12

### Fixed

- Requirements on other DenoSys packages are `^0.11`. In 0.11.0 they were
  still `^0.10`, which for a 0.x version allows only 0.10 releases, so 0.11.0
  packages resolved their siblings to 0.10 or could not be installed. Use
  0.11.1 instead of 0.11.0.

## 0.11.0 – 2026-11-12

- **Breaking:** `Denosys\Events\EventServiceProvider` moved to the framework as `Denosys\Framework\Providers\EventServiceProvider`. Packages no longer ship service providers; the framework wires packages together.
- Requires `psr/http-message`.

## 0.10.0 – 2026-11-12

### Changed

- Released from the [denosyscore/framework](https://github.com/denosyscore/framework)
  monorepo, where this package now lives under `packages/events`. All DenoSys
  packages share one version line, so the version moves from v0.2.3 to
  0.10.0. There are no API changes in this release.
- Requirements on other DenoSys packages are `^0.10`.

## 0.2.3 – 2026-11-12

### Fixed

- Require `denosyscore/contracts`. `EventServiceProvider` implements its
  `ServiceProviderInterface`, so installing this package alone left an
  unloadable class.

