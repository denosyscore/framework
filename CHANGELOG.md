# Changelog

## Unreleased (proposed 0.3.1)

- Honor process environment values in the global `env()` helper when PHP does
  not populate `$_ENV`, preserving explicit `$_ENV` precedence and value casts.
- Skip missing optional environment files before invoking dotenv, preventing
  suppressed PHP read warnings while retaining present-file precedence.
