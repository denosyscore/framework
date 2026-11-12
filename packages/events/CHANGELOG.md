# Changelog

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

