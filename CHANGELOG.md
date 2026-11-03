# Changelog

## Unreleased

- Accept database 0.3 while retaining the supported 0.2 line.
- Add `ValidationException::withMessages()` for direct field-keyed errors.
  This is an additive public API intended for the next compatible minor
  release; the validator constructor remains unchanged.
