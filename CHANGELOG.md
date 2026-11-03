# Changelog

## Unreleased (proposed 0.4.1)

- Convert routing HTTP exceptions to their declared response status in the
  default global middleware stack, retaining CORS and non-HTTP failures.
- Bind a PSR response factory for that middleware unless an application has
  already provided one.

## Unreleased (proposed 0.4.0)

- Accept published database, validation, HTTP, and auth 0.3 releases alongside
  their existing 0.2 lines; exercise both supported dependency combinations.
- Declare PHP 8.4.1 as the minimum, matching the existing Symfony Mailer 8
  runtime requirement, and run framework tests in CI on PHP 8.4 and 8.5.

- Authorize and validate type-hinted HTTP FormRequests before route handlers
  run, retaining authenticated users and current route arguments. Handlers
  that previously bypassed validation may now fail early.

- Honor process environment values in the global `env()` helper when PHP does
  not populate `$_ENV`, preserving explicit `$_ENV` precedence and value casts.
- Add an opt-in Symfony mail provider for explicit DSNs, SMTP, and sendmail.
- Allow compatible queue and console 0.3 releases alongside their 0.2 lines.
- Skip missing optional environment files before invoking dotenv, preventing
  suppressed PHP read warnings while retaining present-file precedence.
