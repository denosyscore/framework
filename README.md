# denosyscore/session

Session management and handlers

## Status

Initial extraction snapshot from denosyscore monorepo as of 2026-02-14.

## Installation

composer require denosyscore/session

## Included Modules

- src/Session/*

## Development

composer validate --strict
find src -type f -name '*.php' -print0 | xargs -0 -n1 php -l

## CI Workflows

- CI: composer validation + PHP syntax lint on push and pull requests.
- Release: GitHub release publication on semantic version tags.
- Dependabot: weekly Composer dependency update checks.

## Compatibility and releases

The Composer requirements accept database 0.2, 0.3, and 0.4, plus HTTP
0.2 and 0.3. CI installs the supported combinations on PHP 8.2 and 8.5. After CI passes on `main`,
create an annotated semantic version tag from that reviewed commit and push
it. The Release workflow publishes a GitHub Release. Confirm the matching
version is available in the Composer package index before updating consumers.
