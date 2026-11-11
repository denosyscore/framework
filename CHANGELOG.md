# Changelog

## 0.9.0 – 2026-11-11

### Removed (breaking)

- Application-specific helpers: `send_email()`, `send_email_with_template()`,
  `render_template()`, `format_currency()`, `rand_float()` and
  `render_flash()`. Send mail through the registered Symfony
  `MailerInterface`, and format money and flash markup in the application.
- `mailgun/mailgun-php`, `geoip2/geoip2`, `html2text/html2text`,
  `symfony/http-client` and `league/flysystem-aws-s3-v3` are no longer
  required. Applications that import them must require them directly; S3 disks
  need `composer require league/flysystem-aws-s3-v3`.
- `Application::isConsole()`; use `runningInConsole()`.
- The `Application::VERSION` constant.

### Changed (breaking)

- `path.view` points at `resources/views`, where the skeleton keeps views,
  instead of `app/Views`.
- `Application::version()` reports the installed `denosyscore/framework`
  version instead of `0.0.1`.
- Raise component floors: filesystem `^0.4.0` (S3 adapter optional), http
  `^0.4.0` (copied container exceptions and debugging leftovers removed), and
  auth `^0.3.3`, console `^0.4.2` and session `^0.2.6`, which accept http 0.4.

## 0.8.1 – 2026-11-11

### Fixed

- Mail transport and URL generator settings stored as `null` (common when a
  config file reads an unset environment variable) fall back to their
  defaults again: SMTP keeps `localhost:587` with required TLS, the mailer
  defaults to SMTP, and the base and asset URLs default to empty strings.
  0.8.0 passed the stored `null` through, which could disable the TLS
  requirement or throw a `TypeError`.
- Configuration lookups traverse `ArrayAccess` values such as `ArrayObject`
  again, as they did before 0.8.0.

## 0.8.0 – 2026-11-11

### Changed (breaking)

- Raise the component floors to the releases that contain data-safety and
  security fixes. Database 0.5 and Routing 0.7.0-alpha.1 contain breaking
  changes (listed in their changelogs), so this is a minor release:
  - Database `^0.5.0`: queries and models no longer change or lose data
    silently; PostgreSQL migrations work.
  - Routing `0.7.0-alpha.1`: group middleware covers every route in a group;
    parameter-plus-wildcard routes no longer crash every request.
  - View `^0.3.0`: templates always compile and escape output.
  - HTTP `^0.3.3`: redirect-back only follows same-origin referers.
  - Cache `^0.2.3`: atomic counters for rate limiting.
  - Auth `^0.3.2`, Console `^0.4.1`, Queue `^0.3.3`, Session `^0.2.5` and
    Validation `^0.3.4` accept Database 0.5.
- CI installs the new minimum versions, including Cache and View.

### Fixed

- `Configuration::has()` resolves dot-separated keys (`has('app.name')`); it
  previously only checked top-level sections and returned false for every
  nested key.
- `Configuration::get()` returns stored `null` and `false` values instead of
  the default, and returns the default unchanged when a key is missing at any
  depth.

## 0.7.0

- Require Console 0.4.0 so the framework distribution includes the corrected
  Symfony MIME mail generator. This drops Console 0.3 from the supported
  distribution and removes the command's unsupported Markdown option.
- Exercise Console 0.4.0 as the minimum published component and send a
  generated message through the framework mail provider in CI.

## 0.6.1

- Make the global `mailer()` helper resolve the registered Symfony
  `MailerInterface` instead of a nonexistent Denosys mailer class.

## 0.6.0

- Define the framework as a tested distribution of current component APIs and
  their minimum published versions, including Support. Consumers can require
  the framework without repeating its component constraints.
- Test minimum and current component resolutions on supported PHP versions.
  Older component release lines are outside this distribution contract.

## 0.5.2

- Require filesystem 0.3.3 and patched Symfony Filesystem 7.4.23 and HTTP
  Client 5.4.56 to exclude GHSA-6prc-h956-hxjq, GHSA-gq53-v8w2-gjhj, and
  GHSA-c7hm-wqwp-f9v8 from supported installations. The framework API is
  unchanged.
- Exercise the filesystem 0.3.3 floor in the published dependency CI matrix.
- Audit every published dependency combination in CI.

## 0.5.1

- Require filesystem 0.3.2 or newer so fresh installs use its corrected third-party dependency floors.
- Exercise the filesystem 0.3.2 floor in the published dependency CI matrix.

## 0.5.0

- Resolve the shared configuration read contract through the current framework configuration binding, including when the application container is replaced.
- Require filesystem 0.3.1 and contracts 0.2.4; filesystem 0.3 changes the manager constructor contract for direct callers.

## 0.4.2

- Accept database 0.4 while retaining the supported 0.2 and 0.3 lines.
- Exercise the published 0.4 dependency combination in CI.

## 0.4.1

- Convert routing HTTP exceptions to their declared response status in the
  default global middleware stack, retaining CORS and non-HTTP failures.
- Bind a PSR response factory for that middleware unless an application has
  already provided one.

## 0.4.0

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
