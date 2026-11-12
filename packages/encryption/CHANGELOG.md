# Changelog

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

- **Breaking:** `Denosys\Encryption\EncryptionServiceProvider` moved to the framework as `Denosys\Framework\Providers\EncryptionServiceProvider`. Packages no longer ship service providers; the framework wires packages together.

## 0.10.0 – 2026-11-12

### Changed

- Released from the [denosyscore/framework](https://github.com/denosyscore/framework)
  monorepo, where this package now lives under `packages/encryption`. All DenoSys
  packages share one version line, so the version moves from v0.2.2 to
  0.10.0. There are no API changes in this release.
- Requirements on other DenoSys packages are `^0.10`.

