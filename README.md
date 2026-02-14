# denosyscore/framework

DenoSys Framework application skeleton for bootstrapping new apps with Composer.

## Create a New Project

```bash
composer create-project denosyscore/framework my-app
```

## After Install

```bash
cd my-app
php cfxp optimize
php -S 127.0.0.1:8000 -t public
```

## What You Get

- HTTP and CLI entry points (`public/index.php`, `cfxp`)
- Application bootstrap (`bootstrap/app.php`)
- Base app provider, controller, and user model
- Starter configuration in `config/`
- Starter routes in `routes/web.php`
- Global helper loading via `support/helpers.php`

## Repository Workflows

- `CI`: composer validation + PHP syntax checks on push/PR
- `Release`: publish GitHub release on semantic tags
- `Dependabot`: weekly Composer dependency checks
