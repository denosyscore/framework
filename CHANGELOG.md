# Changelog

## Unreleased (proposed 0.5.2)

- Require filesystem 0.3.3 and patched Symfony Filesystem 7.4.23 and HTTP
  Client 5.4.56 to exclude GHSA-6prc-h956-hxjq, GHSA-gq53-v8w2-gjhj, and
  GHSA-c7hm-wqwp-f9v8 from supported installations. The framework API is
  unchanged.
- Exercise the filesystem 0.3.3 floor in the published dependency CI matrix.
- Audit every published dependency combination in CI.

## Unreleased (proposed 0.5.1)

- Require filesystem 0.3.2 or newer so fresh installs use its corrected third-party dependency floors.
- Exercise the filesystem 0.3.2 floor in the published dependency CI matrix.

## Unreleased (proposed 0.5.0)

- Resolve the shared configuration read contract through the current framework configuration binding, including when the application container is replaced.
- Require filesystem 0.3.1 and contracts 0.2.4; filesystem 0.3 changes the manager constructor contract for direct callers.

## Unreleased (proposed 0.4.2)

- Accept database 0.4 while retaining the supported 0.2 and 0.3 lines.
- Exercise the published 0.4 dependency combination in CI.

## 0.4.1

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
