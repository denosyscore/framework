# Changelog

## 0.3.1 - 2026-11-03

- Correct rule parameter PHPDoc to describe named keys and mixed values from
  the validator's binder, including integer IDs and list values. Add a
  consumer static-analysis check to CI. Runtime validation behavior is unchanged.

## 0.3.0 - 2026-11-03

- Accept database 0.3 while retaining the supported 0.2 line.
- Add `ValidationException::withMessages()` for direct field-keyed errors.
  This is an additive public API; the validator constructor remains unchanged.
