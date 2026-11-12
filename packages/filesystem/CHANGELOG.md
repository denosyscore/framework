# Changelog

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
