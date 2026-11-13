<?php

declare(strict_types=1);

namespace Denosys\Encryption;

/**
 * Authenticated encryption of strings. Encode structured data yourself
 * (json_encode) before encrypting; nothing is serialized or unserialized.
 */
interface EncrypterInterface
{
    /** @throws EncryptException */
    public function encrypt(string $plaintext): string;

    /** @throws DecryptException when the payload is malformed, tampered with or from an unknown key */
    public function decrypt(string $payload): string;

    /** Whether the payload was encrypted with a key other than the current one. */
    public function needsReEncryption(string $payload): bool;

    /**
     * Decrypts with whichever key made the payload and encrypts again with
     * the current key.
     *
     * @throws DecryptException|EncryptException
     */
    public function reEncrypt(string $payload): string;
}
