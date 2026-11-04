# denosyscore/framework

DenoSys framework runtime core package.

## Installation

```bash
composer require denosyscore/framework
```

## Recommended App Skeleton

Use the official app skeleton for new projects:

```bash
composer create-project denosyscore/app my-app
```

## What This Package Provides

- `Denosys\Application` runtime core
- Bootstrap/config/environment/routing glue under `src/`
- Global framework helpers via `support/helpers.php`
- Transitive installation of all modular `denosyscore/*` runtime packages

Starting with framework 0.6.0, its Composer requirements define a tested
distribution of the modular runtime packages. An application can require the
framework without repeating its component requirements. Framework code and
public component APIs are still available under their `Denosys\` namespaces.
If an application imports a third-party library directly, declare that library
in the application's own Composer requirements. PHP 8.4.1 or newer is required.

## Environment files

`EnvironmentManager::load($directory, $files)` treats missing files in the
ordered filename list as optional. It loads the first present file according
to dotenv's normal precedence and emits no PHP warning when none are present.
The directory itself must exist. Run `composer test` to verify this contract
locally.

The global `env()` helper reads an explicit `$_ENV` value first and falls back
to the process environment through `getenv()`. Process overrides therefore work
in CLI configurations that do not populate `$_ENV`. Missing keys return the
provided default; the usual boolean and null string values are cast.

## Mail provider

Register `Denosys\Mail\MailServiceProvider` with `Application::withProviders()`
when an application needs `Symfony\Component\Mailer\MailerInterface`. The
provider binds a lazy `TransportInterface` and mailer. Set `mail.dsn` for a
Symfony-supported transport, or configure `mail.default` as `smtp` or
`sendmail` with `mail.mailers.smtp.*` or `mail.mailers.sendmail.path`.
The global `mailer()` helper resolves the provider's `MailerInterface` binding;
it is available after registering the provider. Call `send()` with a Symfony
`Email` instance or inject `MailerInterface` into an application service.
Unsupported drivers throw when the mailer is resolved; they never silently
discard messages. A working transport and sender address are required before
sending account or other transactional mail.
Framework 0.7.0 requires Console 0.4.0, whose `make:mail` command generates
Symfony MIME `Email` messages accepted by this provider. Its former Markdown
option is removed because no Markdown renderer is registered by the framework.

## Form requests in route handlers

The framework's registered route invocation strategy constructs a type-hinted
`Denosys\Http\FormRequest` subclass from the current PSR-7 request, supplies
the current authenticated user and route arguments, supplies the framework
container, and calls `validate()` before the handler. A denied
`authorize()` or invalid field rule prevents handler invocation and propagates
through the configured exception pipeline. Each invocation uses its own
request, including when the strategy and handler are reused.

Form-request subclasses should retain the package's `Request` constructor
contract. This behavior is provided by the framework bridge; the standalone
routing package remains independent of HTTP form requests.

## HTTP routing errors

The default router returns PSR responses for routing HTTP exceptions, including
404 for unknown paths and 405 for unsupported methods. Its global HTTP
exception middleware runs inside CORS so those responses retain CORS headers.
Unexpected exceptions continue through the configured exception pipeline.

Framework 0.6.0 and later require database 0.4.1 or newer within that release
line; CI verifies minimum and current published component combinations.

## Filesystem configuration

The framework's `ConfigurationInterface` extends the shared
`Denosys\Contracts\Config\ConfigurationReaderInterface`. During application
bootstrap, the reader binding resolves the current configuration binding,
including after it is replaced. A prepared replacement container passed to
`Application::setContainer()` receives the reader binding if it lacks one.
`FilesystemServiceProvider` reads the shared interface and uses the existing
`path.storage` binding, keeping default local files in the storage `app`
directory. Framework 0.5.1 requires filesystem 0.3.2 and contracts 0.2.4 or newer
within their respective release lines. Release contracts before filesystem,
then framework, before updating a consumer's Composer lockfile.
Framework 0.5.2 raises the filesystem and Symfony Filesystem/HTTP Client
minimums to patched releases without changing the framework API.

## Repository Workflows

- `CI`: composer validation, minimum/current published component installs,
  tests, PHP syntax checks, and security audits on PHP 8.4 and 8.5
- `Release`: publish GitHub release on semantic tags
- `Dependabot`: weekly Composer dependency checks

## Releases

After CI passes on `main`, create an annotated semantic version tag from that
reviewed commit and push it. The Release workflow publishes a GitHub Release.
Confirm the matching version is available in the Composer package index before
updating consumers.
