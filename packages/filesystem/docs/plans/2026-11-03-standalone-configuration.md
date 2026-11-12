# Standalone Filesystem Configuration Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Let the filesystem manager and provider work with declared dependencies outside the framework while preserving the framework's configured private disk root.

**Architecture:** A shared `Denosys\Contracts\Config\ConfigurationReaderInterface` owns the one `get()` method that filesystem needs. Framework's existing configuration interface extends it, and framework binds its existing configuration object under both interfaces. Filesystem requires contracts and container directly, resolves the shared interface, and treats the framework's `path.storage` binding as optional; its manager already has a local-root fallback.

**Tech Stack:** PHP 8.2+, Composer, Flysystem, PHPUnit or an executable PHP integration test, GitHub Actions.

**Compatibility:** Adding the contract is additive within contracts 0.2. Filesystem's manager constructor changes from a framework-owned type to the shared type, so direct constructor callers with custom framework configuration implementations must implement the new interface. Publish filesystem as 0.3 and make framework explicitly depend on it. Framework's configuration class retains its old public interface and application binding; publish framework as a new 0.x minor release with the coordinated dependency.

### Task 1: Shared read contract

**Files:** contracts `src/Config/ConfigurationReaderInterface.php`, `README.md`, `CHANGELOG.md`.

1. Write a downstream regression script that tries to construct `FilesystemManager` with an object implementing the proposed shared contract; run it and confirm the missing interface failure.
2. Define `get(string $key, mixed $default = null): mixed` in the shared contract.
3. Verify PSR-4 loading and Composer validation, document the interface, commit, push, open a linked PR, merge after CI, and release contracts v0.2.4.

### Task 2: Filesystem standalone integration

**Files:** filesystem `composer.json`, `src/Filesystem/FilesystemManager.php`, `src/Filesystem/FilesystemServiceProvider.php`, `tests/standalone.php`, `.github/workflows/ci.yml`, `README.md`, `CHANGELOG.md`.

1. Write and run a standalone test that binds only the shared config interface and resolves the provider's default disk, writes a file, and verifies the path. Confirm it fails against the current provider.
2. Add direct runtime dependencies on contracts v0.2.4 and container, change manager/provider to use the shared interface, and use `path.storage` only when bound.
3. Re-run standalone test, syntax lint, Composer validation, and CI. Document how a standalone caller binds config and the SemVer break. Commit, push, open the issue-linked PR, merge after CI, and release filesystem v0.3.0.

### Task 3: Framework binding and integration

**Files:** framework `composer.json`, `src/Config/ConfigurationInterface.php`, `src/Application.php`, `tests/Integration/FilesystemServiceProviderTest.php`, `README.md`, `CHANGELOG.md`.

1. Write a failing integration test that boots a temporary framework application and proves the provider resolves the same config object via the new contract and honors the private storage path.
2. Extend the existing configuration interface, register the new binding, and widen the filesystem requirement to v0.3.
3. Run the complete framework test matrix and quality checks. Document the release order, commit, push, open a linked PR, merge after CI, and release framework v0.5.0.

### Task 4: Downstream consumption

**Files:** consuming application's `composer.json`, `composer.lock`, package-gap record, verification notes.

1. Update only published package constraints and lockfile; ensure there is no development alias.
2. Run the full application test, static analysis, Composer validation and audit, and a private-disk integration check.
3. Mark the gap resolved with exact versions, PRs, and release order.
