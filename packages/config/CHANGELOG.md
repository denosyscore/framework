# Changelog

## 0.13.0 – 2026-11-12

- Released with the framework's 0.13 line; no changes to this package.

## 0.12.0 – 2026-11-12

- First release. `Repository` reads configuration by dot path; a stored null
  is returned instead of the default and `has()` reports it. Typed readers
  (`string`, `int`, `bool`, `array`) and `ConfigSection`s fail with a
  `ConfigException` naming the path. `ConfigLoader` loads a configuration
  directory and writes an atomic cache. `Environment` reads environment
  variables with one casting rule.
