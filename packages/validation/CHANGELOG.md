# Changelog

## 0.11.0 – 2026-11-12

- **Breaking:** `Denosys\Validation\ValidationServiceProvider` moved to the framework as `Denosys\Framework\Providers\ValidationServiceProvider`. Packages no longer ship service providers; the framework wires packages together.

## 0.10.0 – 2026-11-12

### Changed

- Released from the [denosyscore/framework](https://github.com/denosyscore/framework)
  monorepo, where this package now lives under `packages/validation`. All DenoSys
  packages share one version line, so the version moves from v0.3.5 to
  0.10.0. There are no API changes in this release.
- Requirements on other DenoSys packages are `^0.10`.

## 0.3.5 – 2026-11-12

### Fixed

- The built-in rules work without `ValidationServiceProvider`.
  `Validator::make($data, ['email' => 'required|email'])` used to throw
  "Validation rule 'required' does not exist" unless the provider had scanned
  the rules directory. The validator now falls back to `BuiltInRules::MAP`.
  Rules registered with `Validator::extend()` still take precedence, and
  `Validator::reset()` restores the built-in set.

### Added

- `Validator::hasRule()`, and a README quick start that CI runs after
  installing the package on its own.

## 0.3.4 – 2026-11-11

- Accept `denosyscore/database` 0.5. The test suite passes against 0.5.0.

## 0.3.3

- Remove the HTTP exception response adapter from validation. HTTP v0.3.2
  owns it and preserves the former class name as an alias when both packages
  are installed. Declare a conflict with older HTTP versions to prevent an
  incompatible mixed installation.

## 0.3.2

- Accept database 0.4 while retaining the supported 0.2 and 0.3 lines.

## 0.3.1 - 2026-11-03

- Correct rule parameter PHPDoc to describe named keys and mixed values from
  the validator's binder, including integer IDs and list values. Add a
  consumer static-analysis check to CI. Runtime validation behavior is unchanged.
- Declare the Carbon runtime dependency used by the date, before, and after
  rules. Both Carbon 2.72+ and 3.x are supported.

## 0.3.0 - 2026-11-03

- Accept database 0.3 while retaining the supported 0.2 line.
- Add `ValidationException::withMessages()` for direct field-keyed errors.
  This is an additive public API; the validator constructor remains unchanged.
