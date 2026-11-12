<?php

declare(strict_types=1);

namespace Denosys\Encryption;

interface EncrypterInterface
{
    /**
     * Encrypt the given value.
     *
     * @param  mixed  $value
     * @param bool $serialize
     * @return string
     *
     * @throws EncryptException
     */
    public function encrypt(mixed $value, bool $serialize = true): string;

    /**
     * Decrypt the given payload.
     *
     * @param  string  $payload
     * @param  bool  $unserialize
     * @return mixed
     *
     * @throws DecryptException
     */
    public function decrypt(string $payload, bool $unserialize = true): mixed;

    /**
     * Encrypt a string without serialization.
     *
     * @param string $value
     * @return string
     * @throws EncryptException
     */
    public function encryptString(string $value): string;

    /**
     * Decrypt a string without unserialization.
     *
     * @param string $payload
     * @return string
     * @throws DecryptException
     */
    public function decryptString(string $payload): string;

    /**
     * Get the encryption key that the encrypter is currently using.
     *
     * @return string
     */
    public function getKey(): string;
}
