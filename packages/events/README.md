# denosyscore/events

> **Read-only split.** This package is developed in [denosyscore/framework](https://github.com/denosyscore/framework) under `packages/events`. Open issues and pull requests there.

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


## Class listeners

Register an invokable class instead of a callable to build the listener, and
its dependencies, only when its event is dispatched. The provider resolves it
from any PSR-11 container:

```php
$listeners = new ListenerProvider($container);
$listeners->on(OrderPlaced::class, SendReceipt::class);  // built on dispatch
```

A class listener without a container, or a class without `__invoke`, is
rejected when it is registered.

## Included Modules

- src/Events/* (legacy namespace)
- src/*.php for Denosys\\Events\\* extracted namespace

## Development

composer validate --strict
find src -type f -name '*.php' -print0 | xargs -0 -n1 php -l
