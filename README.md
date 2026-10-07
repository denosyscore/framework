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

## Environment files

`EnvironmentManager::load($directory, $files)` treats missing files in the
ordered filename list as optional. It loads the first present file according
to dotenv's normal precedence and emits no PHP warning when none are present.
The directory itself must exist. Run `composer test` to verify this contract
locally.

## Repository Workflows

- `CI`: composer validation + PHP syntax checks on push/PR
- `Release`: publish GitHub release on semantic tags
- `Dependabot`: weekly Composer dependency checks
