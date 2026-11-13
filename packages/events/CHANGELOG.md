# Changelog

## 0.20.0 – 2026-11-13

- Released with the framework's 0.20 line. This package has no DenoSys
  dependencies; no other changes.

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

- `ListenerProvider::on()` and `Dispatcher::listen()` accept an invokable class
  name as well as a callable; the class is resolved from a PSR-11 container
  passed to `ListenerProvider` only when its event fires. Registering a class
  listener without a container, or a class without `__invoke`, throws.
- **Breaking:** `RequestHandling` and `ResponseReady` moved to the framework
  (`Denosys\Framework\Http\Events`); this package no longer requires
  `psr/http-message` and now requires `psr/container`.
- `ListenerProvider` is final. Tests run in CI.

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

