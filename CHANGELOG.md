# Changelog

## Unreleased (proposed 0.4.0 lifecycle change)

- Authorize and validate type-hinted HTTP FormRequests before route handlers
  run. Handlers that previously bypassed validation may now fail early.

## Unreleased (proposed 0.3.1)

- Add an opt-in Symfony mail provider for explicit DSNs, SMTP, and sendmail.
- Skip missing optional environment files before invoking dotenv, preventing
  suppressed PHP read warnings while retaining present-file precedence.
