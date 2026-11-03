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

## Direct field errors

When a field-specific failure is discovered after ordinary rule validation,
raise it without constructing an empty validator in the caller:

```php
throw \Denosys\Validation\ValidationException::withMessages([
    'email' => 'The email address is unavailable.',
]);
```

Values may be a string or a non-empty list of strings. The existing
`ValidationException(Validator $validator)` constructor remains supported.

The `unique:accounts,email,1` rule checks the `email` column in `accounts`
while excluding the row with `id = 1`. Its third positional parameter is
named `ignore_id` by `Unique::parameterNames()`.

## HTTP-uploaded files

The `file` rule accepts valid PSR-7 uploads from any source. Add
`http_uploaded` when a browser-facing form must require a file received by
PHP's HTTP upload handling:

```php
$validator = Validator::make(
    ['document' => $uploadedFile],
    ['document' => 'required|file|http_uploaded|mimes:jpg,png,pdf'],
);
```

The rule checks a successful, nonempty upload and calls `is_uploaded_file()`
on its stream path. It is intended for validation before moving the upload.

## Development

composer validate --strict
find src tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l
composer test

## CI Workflows

- CI: Composer validation, isolated installation, PHP 8.2/8.5 tests, and
  syntax lint on push and pull requests.
- Release: GitHub release publication on semantic version tags.
- Dependabot: weekly Composer dependency update checks.
