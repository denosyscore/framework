# Changelog

## 0.11.0 – 2026-11-12

- **Breaking:** the provider interfaces moved to the framework as `Denosys\Framework\Provider` and `DeferrableProvider`; the unused container interfaces are removed; `ConfigurationReaderInterface` is renamed `ConfigReader`.
- Adds `View\TemplateRenderer`. Requires no packages.

## 0.10.0 – 2026-11-12

### Changed

- Released from the [denosyscore/framework](https://github.com/denosyscore/framework)
  monorepo, where this package now lives under `packages/contracts`. All DenoSys
  packages share one version line, so the version moves from v0.2.4 to
  0.10.0. There are no API changes in this release.
- Requirements on other DenoSys packages are `^0.10`.

## Unreleased

- Add a minimal configuration read contract for packages that must remain independent of the framework.
