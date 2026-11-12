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
- Every DenoSys component, developed in this repository under `packages/`

Starting with 0.10.0 this repository is the DenoSys monorepo. The framework
package `replace`s every component, so an application that requires the
framework installs all components from this one package, at the same version.
Each component is also published on its own (for example
`denosyscore/database`) from a read-only split repository, for code that needs
only that component. Framework code and
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

Register `Denosys\Framework\Providers\MailServiceProvider` with `Application::withProviders()`
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

## Repository layout

| Path | Contents |
|---|---|
| `src/`, `support/`, `tests/` | The framework package itself |
| `packages/<name>/` | One component per directory, each with its own `composer.json`, README, tests and PHPStan baseline |
| `src/Framework/` | Framework-owned integration: service providers (`Providers/`), the HTTP kernel, routing integration and framework console commands |
| `bin/package-ci` | Installs one package on its own, with sibling packages from this checkout, and runs its checks; `layers` checks package boundaries |

Every package directory is published to the read-only repository
`denosyscore/<name>` by the **Split packages** workflow. Pull requests opened
there are closed with a pointer back here.

## Working on a package

```bash
bin/package-ci install packages/database
bin/package-ci check database
bin/package-ci quickstart database
```

`install` writes a git-ignored `composer.monorepo.json` that resolves sibling
packages from `packages/`, so a change is tested against the other packages at
the same commit. A package that uses a class it does not require fails here,
even though the root autoloader would hide it.

## Package boundaries

Packages are grouped into tiers in `composer.json` under `extra.denosys.tiers`:
foundation (contracts, container, events), infrastructure (database, cache,
encryption, hashing, filesystem), HTTP (http, routing, session, validation,
rate-limiter) and features (auth, queue, view, console). A package may require
only packages in its own or a lower tier, without cycles, and its code may use
only the DenoSys packages it requires. Packages contain no service providers:
the framework's providers in `src/Framework/Providers` wire them together.

```bash
bin/package-ci layers
```

## Repository workflows

- **CI:** framework tests, static analysis, an audit and the package boundary check. Each package is installed alone and its tests, PHPStan baseline and README quick start are run. Database tests also run against MySQL, MariaDB and PostgreSQL, and there is an HTTP and session integration check.
- **Split packages:** publishes `packages/*` to the package repositories on every push to `main` and on version tags. It needs the `SPLIT_TOKEN` secret.
- **Release:** creates the GitHub release for a tag.
- **Dependabot:** weekly Composer updates for the root and every package, and GitHub Actions updates.

## Releases

All packages share one version line (`0.10`). To release:

1. Make sure CI passes on `main`.
2. Push an annotated tag from that commit, for example `git tag -a v0.10.1 -m v0.10.1 && git push origin v0.10.1`.
3. The framework release is published here. **Split packages** tags the package repositories: a minor release (`vX.Y.0`) tags every package, and a patch release tags only packages whose directory changed since the previous tag.
4. Confirm the versions on Packagist before updating consumers.

Between releases, a package's requirements on other DenoSys packages use the current line (`^0.10`). When opening a new minor line, update `extra.branch-alias` in the root and every package, and those requirements, together.
