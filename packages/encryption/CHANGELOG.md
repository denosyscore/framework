# Changelog

## 0.11.0 – 2026-11-12

- **Breaking:** `Denosys\Encryption\EncryptionServiceProvider` moved to the framework as `Denosys\Framework\Providers\EncryptionServiceProvider`. Packages no longer ship service providers; the framework wires packages together.

## 0.10.0 – 2026-11-12

### Changed

- Released from the [denosyscore/framework](https://github.com/denosyscore/framework)
  monorepo, where this package now lives under `packages/encryption`. All DenoSys
  packages share one version line, so the version moves from v0.2.2 to
  0.10.0. There are no API changes in this release.
- Requirements on other DenoSys packages are `^0.10`.

