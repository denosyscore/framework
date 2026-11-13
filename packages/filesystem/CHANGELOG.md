# Changelog

## 0.21.0 – 2026-11-13

- Released with the framework's 0.21 line. Requires its DenoSys dependencies
  at `^0.21`; no other changes.

## 0.20.0 – 2026-11-13

- Released with the framework's 0.20 line. Requires its DenoSys dependencies
  at `^0.20`; no other changes.

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

- **Breaking:** `Denosys\Filesystem\FilesystemServiceProvider` moved to the framework as `Denosys\Framework\Providers\FilesystemServiceProvider`. Packages no longer ship service providers; the framework wires packages together.
- Requires `psr/http-message` and `league/flysystem-local`; no longer `symfony/filesystem`, container or events.

## 0.10.0 – 2026-11-12

### Changed

- Released from the [denosyscore/framework](https://github.com/denosyscore/framework)
  monorepo, where this package now lives under `packages/filesystem`. All DenoSys
  packages share one version line, so the version moves from v0.4.0 to
  0.10.0. There are no API changes in this release.
- Requirements on other DenoSys packages are `^0.10`.

## v0.4.0 – 2026-11-11

### Changed (breaking)

- `league/flysystem-aws-s3-v3` is now suggested instead of required, so
  applications without S3 disks no longer install the AWS SDK. Applications
  that configure an `s3` disk must run
  `composer require league/flysystem-aws-s3-v3`; resolving an `s3` disk
  without it throws a `RuntimeException` that names that command.
- CI installs without development dependencies to test that error.

## v0.3.3

- Require Symfony Filesystem 7.4.23 or newer in the 7.x line to exclude the
  path containment vulnerability in GHSA-6prc-h956-hxjq. The package API is
  unchanged.

## v0.3.2

- Raise the Flysystem minimum to its patched path-normalization release and exclude advised AWS SDK, Guzzle, PSR-7, and JMESPath transitive versions without adding unnecessary direct dependencies.
- Audit both current and lowest supported Composer resolutions in CI.

## v0.3.1

- Exclude container and events releases with invalid autoload mappings from the supported dependency range.
- Exercise lowest and highest Composer resolutions in CI.

## v0.3.0

- Use an installable shared configuration read contract for the filesystem manager and provider.
- Declare the container dependency used by the provider and allow standalone hosts to omit the framework storage-path binding.
- Add a standalone integration test on PHP 8.2 and 8.5.

**Compatibility:** Direct `FilesystemManager` callers must pass a `ConfigurationReaderInterface` implementation. Framework hosts must bind the shared contract before resolving the provider; existing framework configuration can implement both interfaces.
