<?php

declare(strict_types=1);

namespace Denosys\Encryption;

use SodiumException;

/**
 * A secret-box key and its id. The id is the first eight hex characters of
 * the key's SHA-256, so it names the key in payloads without revealing it.
 */
final readonly class Key
{
    public string $id;

    private function __construct(private string $bytes)
    {
        $this->id = substr(hash('sha256', $bytes), 0, 8);
    }

    /**
     * Reads a base64 key, with or without a "base64:" prefix.
     *
     * @throws InvalidKeyException
     */
    public static function fromBase64(string $base64): self
    {
        $encoded = str_starts_with($base64, 'base64:') ? substr($base64, 7) : $base64;

        try {
            $bytes = sodium_base642bin($encoded, SODIUM_BASE64_VARIANT_ORIGINAL);
        } catch (SodiumException $e) {
            throw new InvalidKeyException(message: 'Key must be a valid base64 string.', previous: $e);
        }

        if (strlen($bytes) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new InvalidKeyException('Key must be ' . SODIUM_CRYPTO_SECRETBOX_KEYBYTES . ' bytes.');
        }

        return new self($bytes);
    }

    public static function generate(): self
    {
        return new self(sodium_crypto_secretbox_keygen());
    }

    public function toBase64(): string
    {
        return sodium_bin2base64($this->bytes, SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    /** @internal for Encrypter */
    public function bytes(): string
    {
        return $this->bytes;
    }
}
