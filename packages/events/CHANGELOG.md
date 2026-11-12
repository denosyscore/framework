# Changelog

## 0.2.3 – 2026-11-12

### Fixed

- Require `denosyscore/contracts`. `EventServiceProvider` implements its
  `ServiceProviderInterface`, so installing this package alone left an
  unloadable class.

