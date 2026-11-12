# denosyscore/events

Event dispatching and listener provider components

## Installation

```bash
composer require denosyscore/events
```

## Quick start

A PSR-14 event dispatcher. It needs no container or configuration.

<!-- quickstart -->
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Denosys\Events\Dispatcher;
use Denosys\Events\ListenerProvider;

final class OrderPlaced
{
    public function __construct(public readonly int $orderId) {}
}

$listeners = new ListenerProvider();
$listeners->addListener(OrderPlaced::class, function (OrderPlaced $event): void {
    echo "Send receipt for order {$event->orderId}\n";
});

// A PSR-14 dispatcher: any listener provider and any event object.
$events = new Dispatcher($listeners);
$events->dispatch(new OrderPlaced(1001));  // Send receipt for order 1001
```

## Included Modules

- src/Events/* (legacy namespace)
- src/*.php for Denosys\\Events\\* extracted namespace

## Development

composer validate --strict
find src -type f -name '*.php' -print0 | xargs -0 -n1 php -l

## CI Workflows

- CI: composer validation + PHP syntax lint on push and pull requests.
- Release: GitHub release publication on semantic version tags.
- Dependabot: weekly Composer dependency update checks.
