# denosyscore/validation

Validation engine and rules

## Status

Initial extraction snapshot from denosyscore monorepo as of 2026-02-14.

## Installation

composer require denosyscore/validation

## Included Modules

- src/Validation/*

## Database-backed rules

The `unique` and `exists` rules resolve the database package's
`Denosys\Database\Connection\Connection` binding lazily when a rule first
runs. The legacy `db` binding remains a fallback. When both bindings are
present, the typed connection takes precedence. Register the database provider
before validating with these rules.

The `unique:accounts,email,1` rule checks the `email` column in `accounts`
while excluding the row with `id = 1`. Its third positional parameter is
named `ignore_id` by `Unique::parameterNames()`.

## Development

composer validate --strict
find src tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l
composer test

## CI Workflows

- CI: Composer validation, isolated installation, PHP 8.2/8.5 tests, and
  syntax lint on push and pull requests.
- Release: GitHub release publication on semantic version tags.
- Dependabot: weekly Composer dependency update checks.
