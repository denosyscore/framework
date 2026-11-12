<?php

declare(strict_types=1);

namespace Denosys\Encryption;

use SodiumException;

class Encrypter implements EncrypterInterface
{
    private readonly string $decodedKey;

    /**
     * Create a new encrypter instance.
     *
     * @param string $key
     *
     * @throws InvalidKeyException
     */
    public function __construct(private readonly string $key)
    {
        try {
            $this->decodedKey = sodium_base642bin($key, SODIUM_BASE64_VARIANT_ORIGINAL);
        } catch (SodiumException $e) {
            throw new InvalidKeyException(message: 'Key must be a valid base64 string.', previous: $e);
        }


        if (!self::isValidKey($this->decodedKey)) {
            throw new InvalidKeyException('Key must be ' . SODIUM_CRYPTO_SECRETBOX_KEYBYTES . ' bytes.');
        }
    }

    /**
     * Check if the given key is valid.
     *
     * @param string $key
     *
     * @return bool
     */
    public static function isValidKey(string $key): bool
    {
        return mb_strlen($key, '8bit') === SODIUM_CRYPTO_SECRETBOX_KEYBYTES;
    }

    /**
     * Encrypt the given value.
     *
     * @param mixed $value
     * @param bool $serialize
     *
     * @return string
     *
     * @throws EncryptException
     */
    public function encrypt(mixed $value, bool $serialize = true): string
    {
        $value = $serialize ? serialize($value) : $value;

        try {
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

            $cipher = sodium_crypto_secretbox($value, $nonce, $this->decodedKey);

            $result = sodium_bin2base64($nonce . $cipher, SODIUM_BASE64_VARIANT_ORIGINAL);

            // Zero out the plaintext strings before returning it.
            // This is to prevent leakage of any sensitive data.
            sodium_memzero($value);
            sodium_memzero($nonce);
            sodium_memzero($cipher);
        } catch (SodiumException $e) {
            throw new EncryptException(message: 'Could not encrypt the data.', previous: $e);
        }

        return $result;
    }

    /**
     * Decrypts the given payload.
     *
     * @param string $payload The encrypted payload to decrypt.
     * @param bool $unserialize Whether to unserialize the decrypted data. Default is true.
     *
     * @return mixed The decrypted data. If $unserialize is true, it returns the unserialized data,
     * otherwise it returns the decrypted string.
     *
     * @throws DecryptException If the data cannot be decrypted.
     */
    public function decrypt(string $payload, bool $unserialize = true): mixed
    {
        try {
            $payload = sodium_base642bin($payload, SODIUM_BASE64_VARIANT_ORIGINAL);
            $nonce = mb_substr($payload, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
            $payload = mb_substr($payload, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
            $decrypted = sodium_crypto_secretbox_open($payload, $nonce, $this->decodedKey);

            if ($decrypted === false) {
                throw new DecryptException(message: 'Could not decrypt the data.');
            }

            // Zero out the plaintext strings before returning it.
            // This is to prevent leakage of any sensitive data.
            sodium_memzero($nonce);
            sodium_memzero($payload);
        } catch (SodiumException $e) {
            throw new DecryptException(message: 'Could not decrypt the data.', previous: $e);
        }

        return $unserialize ? unserialize($decrypted) : $decrypted;
    }

    /**
     * Encrypt a string without serialization.
     * 
     * Convenience method for encrypting simple strings.
     *
     * @param string $value
     * @return string
     * @throws EncryptException
     */
    public function encryptString(string $value): string
    {
        return $this->encrypt($value, false);
    }

    /**
     * Decrypt a string without unserialization.
     * 
     * Convenience method for decrypting simple strings.
     *
     * @param string $payload
     * @return string
     * @throws DecryptException
     */
    public function decryptString(string $payload): string
    {
        return $this->decrypt($payload, false);
    }

    /**
     * Generate a new encryption key.
     *
     * @return string
     *
     * @throws KeyGenerationException
     */
    public static function generateKey(): string
    {
        try {
            return sodium_bin2base64(sodium_crypto_secretbox_keygen(), SODIUM_BASE64_VARIANT_ORIGINAL);
        } catch (SodiumException $e) {
            throw new KeyGenerationException(message: 'Could not generate a key.', previous: $e);
        }
    }

    /**
     * Get the encryption key that the encrypter is currently using.
     *
     * @throws SodiumException
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Create a new encrypter with a different key for key rotation.
     * 
     * This allows decrypting data encrypted with an old key
     * and re-encrypting with the new key.
     *
     * @param string $newKey The new encryption key
     * @return self A new Encrypter instance with the new key
     * @throws InvalidKeyException
     */
    public static function withNewKey(string $newKey): self
    {
        return new self($newKey);
    }

    /**
     * Re-encrypt data from an old key to the current key.
     * 
     * Useful during key rotation to migrate encrypted data.
     *
     * @param string $payload The encrypted payload (using old key)
     * @param string $oldKey The old encryption key
     * @param bool $serialize Whether to serialize (for complex types)
     * @return string The re-encrypted payload (using current key)
     * @throws DecryptException|EncryptException|InvalidKeyException
     */
    public function reEncrypt(string $payload, string $oldKey, bool $serialize = true): string
    {
        $oldEncrypter = new self($oldKey);
        $decrypted = $oldEncrypter->decrypt($payload, $serialize);
        return $this->encrypt($decrypted, $serialize);
    }
}
