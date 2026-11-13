<?php

declare(strict_types=1);

namespace Denosys\Encryption;

/**
 * The key that encrypts, and the previous keys that still decrypt while
 * data is re-encrypted after a rotation.
 */
final readonly class KeyRing
{
    /** @var list<Key> */
    public array $previous;

    /** @throws InvalidKeyException when two keys share an id (the same key twice) */
    public function __construct(public Key $current, Key ...$previous)
    {
        $this->previous = array_values($previous);

        $ids = array_map(static fn (Key $key): string => $key->id, $this->all());
        if (count($ids) !== count(array_unique($ids))) {
            throw new InvalidKeyException('A key ring cannot hold two keys with the same id.');
        }
    }

    public function find(string $id): ?Key
    {
        foreach ($this->all() as $key) {
            if (hash_equals($key->id, $id)) {
                return $key;
            }
        }

        return null;
    }

    /** @return list<Key> the current key first */
    public function all(): array
    {
        return [$this->current, ...$this->previous];
    }
}
