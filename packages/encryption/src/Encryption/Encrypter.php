<?php

declare(strict_types=1);

namespace Denosys\Encryption;

use SodiumException;

/**
 * libsodium secret-box encryption with a key ring. A payload is
 * "v1.<key id>.<base64 nonce and ciphertext>", so a rotated key keeps
 * decrypting what it encrypted. Payloads written before key ids (a bare
 * base64 string) are tried with every key in the ring.
 */
final readonly class Encrypter implements EncrypterInterface
{
    private const VERSION = 'v1';

    public function __construct(private KeyRing $keys)
    {
    }

    /** A new random key, base64 encoded, for APP_KEY. */
    public static function generateKey(): string
    {
        return Key::generate()->toBase64();
    }

    public function encrypt(string $plaintext): string
    {
        try {
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $cipher = sodium_crypto_secretbox($plaintext, $nonce, $this->keys->current->bytes());
            $body = sodium_bin2base64($nonce . $cipher, SODIUM_BASE64_VARIANT_ORIGINAL);
        } catch (SodiumException $e) {
            throw new EncryptException(message: 'Could not encrypt the data.', previous: $e);
        }

        return self::VERSION . '.' . $this->keys->current->id . '.' . $body;
    }

    public function decrypt(string $payload): string
    {
        $parts = explode('.', $payload);
        if (count($parts) === 3 && $parts[0] === self::VERSION) {
            $key = $this->keys->find($parts[1]) ?? throw new DecryptException('The payload was encrypted with an unknown key.');

            return $this->open($parts[2], $key) ?? throw new DecryptException('Could not decrypt the data.');
        }

        foreach ($this->keys->all() as $key) {
            $plaintext = $this->open($payload, $key);
            if ($plaintext !== null) {
                return $plaintext;
            }
        }

        throw new DecryptException('Could not decrypt the data.');
    }

    public function needsReEncryption(string $payload): bool
    {
        return !str_starts_with($payload, self::VERSION . '.' . $this->keys->current->id . '.');
    }

    public function reEncrypt(string $payload): string
    {
        return $this->encrypt($this->decrypt($payload));
    }

    /** The plaintext, or null when the body is malformed or fails authentication. */
    private function open(string $body, Key $key): ?string
    {
        try {
            $bytes = sodium_base642bin($body, SODIUM_BASE64_VARIANT_ORIGINAL);
        } catch (SodiumException) {
            return null;
        }

        if (strlen($bytes) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES) {
            return null;
        }

        $nonce = substr($bytes, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($bytes, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        try {
            $plaintext = sodium_crypto_secretbox_open($cipher, $nonce, $key->bytes());
        } catch (SodiumException) {
            return null;
        }

        return $plaintext === false ? null : $plaintext;
    }
}
