# Changelog

## 0.3.4 – 2026-11-11

- Accept `denosyscore/database` 0.5. The test suite passes against 0.5.0.

## 0.3.3

- Remove the HTTP exception response adapter from validation. HTTP v0.3.2
  owns it and preserves the former class name as an alias when both packages
  are installed. Declare a conflict with older HTTP versions to prevent an
  incompatible mixed installation.

## 0.3.2

- Accept database 0.4 while retaining the supported 0.2 and 0.3 lines.

## 0.3.1 - 2026-11-03

- Correct rule parameter PHPDoc to describe named keys and mixed values from
  the validator's binder, including integer IDs and list values. Add a
  consumer static-analysis check to CI. Runtime validation behavior is unchanged.
- Declare the Carbon runtime dependency used by the date, before, and after
  rules. Both Carbon 2.72+ and 3.x are supported.

## 0.3.0 - 2026-11-03

- Accept database 0.3 while retaining the supported 0.2 line.
- Add `ValidationException::withMessages()` for direct field-keyed errors.
  This is an additive public API; the validator constructor remains unchanged.
