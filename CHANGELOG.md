# Changelog

## 0.17.0 – 2026-11-12

Phase 3, step 1: the query builder (finding F24).

### Changed (breaking)

- `denosyscore/database` queries are immutable; see that package's changelog.
  Code that calls a builder method as a statement (`$query->where(...);`) must
  use the result, and nested `where()` callbacks must return the query.

### Release tooling

- `bin/package-ci versions` also checks projects nested inside packages
  (such as the HTTP session integration check), which must require the
  current line too.

## 0.16.0 – 2026-11-12

Console (finding F20). This completes Phase 2.

### Changed (breaking)

- The console `Kernel` is final and configured by constructor arguments;
  applications no longer subclass it. Delete `app/Console/Kernel.php`. The
  `console.kernel` setting and the fallback to `\App\Console\Kernel` are
  removed, as is `console.command_namespace`: application commands are
  discovered in `app/Console/Commands` under the namespace composer.json maps
  to `app/` (`App\` when it maps none).
- Commands live in the package that owns them: `migrate`,
  `migrate:rollback`, `migrate:status`, `migrate:fresh` and `db:seed` move to
  `Denosys\Database\Console`, and `queue:work` to `Denosys\Queue\Console`.
  Their framework providers register them through `CommandRegistry`.
- `denosyscore/console` moves to the foundation tier and requires only the
  container and Symfony Console.

### Added

- `#[AsCommand('name', 'description')]` on a plain invokable class, with an
  optional static `definition()`; its metadata is read without building it.
  `make:command` generates this style.
- `Application::console()` returns the booted console application;
  `runConsole()` runs it.

## 0.15.0 – 2026-11-12

Lazy listeners (finding F21).

### Changed (breaking)

- `Denosys\Events\RequestHandling` and `ResponseReady` move to
  `Denosys\Framework\Http\Events`, next to the kernel that dispatches them.

### Added

- Listeners can be invokable class names:
  `$events->listen(UserRegistered::class, SendWelcomeMail::class)` builds the
  listener, with its constructor dependencies, only when the event fires. The
  framework's listener provider resolves them from the application container.

## 0.14.1 – 2026-11-12

### Fixed

- A failed boot no longer counts as booted. Every later `boot()`, request or
  command reports the original error instead of running on a partly
  initialized application.
- `without()` rejects a class that is not a default provider, so a misspelled
  exclusion no longer leaves the provider enabled silently.
- A provider listed under differently cased names is registered once.
- `setContainer()` refuses to replace the container after boot, when the new
  container would have no providers.
- Deferred providers are documented as booting on first use, not at boot.

## 0.14.0 – 2026-11-12

One boot path (findings F09 and F16).

### Changed (breaking)

- `Application::configure(basePath:)` starts configuration and `create()`
  finishes it; the application boots on first use. `new Application(...)`
  and the `with*()` methods still work.
- `boot()` registers and boots every provider once, with one event
  dispatcher, for HTTP requests and console commands alike. It replaces
  `bootstrap()` and the console's separate `bootstrapServices()`, which
  created a second dispatcher. `getHttpKernel()` is removed.
- `Denosys\Framework\Http\Kernel` takes only the container, uses the
  dispatcher and exception handler that `boot()` created, and no longer boots
  anything; its `getContainer()`, `getEventDispatcher()` and
  `getExceptionHandler()` are removed.
- Providers come from one list: `Application::defaultProviders()` (now
  including mail, filesystem, queue and hashing, which applications used to
  register by hand), minus `without()`, plus `withProviders()`. Each is
  registered once. `bootstrap/providers.php` is no longer read, and boot
  stops with an error if it still lists providers.
- A provider class that does not exist, or does not implement `Provider`, is
  an error. It used to be skipped silently.
- The `KernelBooting` and `KernelBooted` events are removed from
  `denosyscore/http`. They fired only on the HTTP path and nothing listened.

## 0.13.0 – 2026-11-12

The container (finding F11): about 550 lines instead of 6,787, with
parameter attributes.

### Changed (breaking)

- `denosyscore/container` keeps `bind`, `singleton`, `instance`, `alias`,
  `getAlias`, `extend`, `setDeferredResolver`, `has` and `get`, and constructor
  autowiring. Removed, with no consumers: contextual bindings (`when`),
  tagging, scoped and lazy bindings, decorators, container middleware,
  `call()`, multi-resolution, mocks and spies, the validator, the profiler,
  resolution events and the compiler.
- The compiled container is gone: `container:warmup` and `container:clear`
  are removed, `optimize` and `optimize:clear` no longer run them, and
  `Application` no longer loads `storage/core/cache/container.php` (it used
  to swallow errors while doing so). `Application::containerCacheFile()` is
  removed.
- `get()` on an identifier the container cannot provide throws
  `NotFoundException` (PSR-11) instead of a wrapped
  `ContainerResolutionException`; an unbound interface is such an identifier.
  Routing's handler resolvers already catch this to fall back.
- The container no longer depends on `denosyscore/events`.

### Added

- `#[Config('path')]` injects a configuration value through the bound
  `ConfigReader`, using the parameter's default when the setting is absent;
  `#[Inject('id')]` injects a specific binding. Any attribute implementing
  `Denosys\Container\ParameterResolver` works the same way.

## 0.12.0 – 2026-11-12

Configuration (finding F04): one correct way to read settings and one way to
read the environment.

### Added

- `denosyscore/config`, a standalone package in the foundation tier:
  `Repository` (dot paths where a stored `null` is a value, `has()` that sees
  it, typed `string()`/`int()`/`bool()`/`array()` and `section()`),
  `ConfigSection`, `ConfigLoader` (directory loading and an atomic cache file)
  and `Environment`.

### Changed (breaking)

| Before | After |
|---|---|
| `Denosys\Config\Configuration`, `Denosys\Config\ConfigurationInterface` | `Denosys\Config\Repository` (or the `ConfigReader` contract where only `get()` is needed); the container binds `Repository`, `ConfigReader` and the `config` alias to one instance |
| `Denosys\Environment\EnvironmentManager` | `Denosys\Config\Environment` |
| `Configuration::saveToCache()` / `clearCache()` | `ConfigLoader::writeCache()` / `clearCache()` |
| `ValidationExceptionHandler` read `APP_DEBUG` from the process | it takes `bool $debug` in its constructor |

- `env()` reads through the application's `Environment`, so it casts exactly
  like config files: `(int)42` and `(float)1.5` now become numbers in both.
- `Application::isLocal()` reads `app.env` from configuration.
- The logger reads `app.env` and `logging.path` from configuration (map
  `LOG_PATH` in `config/logging.php`), and the exception handler reads
  `app.debug` and `app.env`, instead of the process environment.
- The `migrate` and `migrate:rollback` commands decide whether they run in
  production from `app.env`; without configuration they assume production.

### Fixed

- `get()` returned a default that had been cached for an earlier call with a
  different default.

## 0.11.1 – 2026-11-12

### Fixed

- Every package's requirements on its siblings are `^0.11` and the branch
  alias is `0.11.x-dev`. 0.11.0 shipped them on the 0.10 line, so its packages
  resolved 0.10 siblings and `denosyscore/http` 0.11.0 could not be installed.

### Added

- `bin/package-ci versions` checks that sibling requirements and branch
  aliases share one line. CI runs it on every change, and the split workflow
  runs it with `--tag` before tagging, refusing a tag outside the declared
  line.

## 0.11.0 – 2026-11-12

Package boundaries. Every package now requires exactly the DenoSys packages
its code uses, no package depends on a package in a higher tier, and there are
no dependency cycles. `bin/package-ci layers` checks all three in CI.

### Changed (breaking)

| Before | After |
|---|---|
| `Denosys\Contracts\ServiceProviderInterface` | `Denosys\Framework\Provider` |
| `Denosys\Contracts\DeferrableProviderInterface` | `Denosys\Framework\DeferrableProvider` |
| `Denosys\{Package}\…ServiceProvider` (all 19, including `Denosys\Mail\MailServiceProvider`, `Denosys\Logging\LoggerServiceProvider`, `Denosys\Routing\RouterServiceProvider`) | `Denosys\Framework\Providers\…ServiceProvider` |
| `Denosys\Http\Kernel` | `Denosys\Framework\Http\Kernel` |
| `Denosys\Routing\RouteLoader`, `RouteCache`, `Contracts\UrlRoutable`, `ParameterResolvers\FormRequestResolver`, `ParameterResolvers\RouteModelBindingResolver`, `Strategy\ModelBindingInvocationStrategy` (framework classes inside the routing namespace) | `Denosys\Framework\Routing\…` |
| `Denosys\Console\Command\{ConfigCache,ConfigClear,RoutesCache,RoutesClear,ViewClear}Command` | `Denosys\Framework\Console\…Command` |
| `Denosys\Http\Middleware\ThrottleRequestsMiddleware` | `Denosys\RateLimiter\Middleware\ThrottleRequestsMiddleware` |
| `Denosys\Contracts\Config\ConfigurationReaderInterface` | `Denosys\Contracts\Config\ConfigReader` |
| `Denosys\Support\Collection` | `Illuminate\Support\Collection` (`denosyscore/support` is retired) |
| `ResponseFactory::setViewEngine()` / `getViewEngine()` | `setTemplateRenderer()` / `getTemplateRenderer()` with `Denosys\Contracts\View\TemplateRenderer` |

- The contracts package's unused container interfaces are removed.
- The root autoloader maps only the framework's own namespaces (`Bootstrap`,
  `Config`, `Environment`, `Framework`, `Mail`, and `Denosys\Application`)
  instead of claiming all of `Denosys\`.

### Fixed

- **Security:** the fallback 401, 403, 404 and 500 pages escape their message.
- The cache provider's default directory is `cache` under `path.storage`. It
  used to resolve inside the package, which is `vendor/` in an application.
- Packages declare the third-party libraries they use: database requires
  `doctrine/inflector`, http requires `filp/whoops` and `psr/log`, filesystem
  requires `league/flysystem-local`, and events and session require the PSR
  interface packages their classes implement.

### Added

- `Denosys\Contracts\View\TemplateRenderer`, implemented by `ViewEngine`.
- The console `Kernel` accepts commands owned by the host application; the
  framework passes its own through `Application::consoleCommands()`.
- `bin/package-ci layers` and a **Package boundaries** CI job. Tiers are
  declared in `extra.denosys.tiers`: foundation (contracts, container,
  events), infrastructure, HTTP, and features.

## 0.10.0 – 2026-11-12

### Changed (breaking)

- This repository is now the DenoSys monorepo. All 18 components are
  developed under `packages/` with their full history, and are published to
  their existing read-only repositories by the **Split packages** workflow.
- The framework `replace`s every component instead of requiring it. An
  application that requires `denosyscore/framework` gets every component at
  the framework's version, from this one package. Applications that also
  require a component directly must allow `^0.10`.
- All packages share the `0.10` version line.

### Added

- `bin/package-ci`, which installs one package on its own, with sibling
  packages from this checkout, and runs its tests, PHPStan baseline and README
  quick start. CI runs it for every package on PHP 8.2 and 8.5.

## 0.9.2 – 2026-11-12

### Fixed

- Require validation `^0.3.5`, whose built-in rules work without the service
  provider, and rate-limiter `^0.2.3`, which accepts keys containing `:` such
  as IPv6 addresses.

## 0.9.1 – 2026-11-12

### Fixed

- Require routing `0.7.0-alpha.2`, which keeps several parameter resolvers of
  one class instead of reporting a false circular dependency.

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
