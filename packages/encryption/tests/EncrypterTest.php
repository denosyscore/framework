<?php

declare(strict_types=1);

use Denosys\Encryption\DecryptException;
use Denosys\Encryption\Encrypter;
use Denosys\Encryption\InvalidKeyException;
use Denosys\Encryption\Key;
use Denosys\Encryption\KeyRing;
use PHPUnit\Framework\TestCase;

final class EncrypterTest extends TestCase
{
    public function testItRoundTripsAStringAndNamesTheKey(): void
    {
        $key = Key::generate();
        $encrypter = new Encrypter(new KeyRing($key));

        $payload = $encrypter->encrypt('secret value');

        self::assertStringStartsWith('v1.' . $key->id . '.', $payload);
        self::assertSame('secret value', $encrypter->decrypt($payload));
        self::assertFalse($encrypter->needsReEncryption($payload));
    }

    public function testAPreviousKeyStillDecryptsAndAsksForReEncryption(): void
    {
        $old = Key::generate();
        $payload = (new Encrypter(new KeyRing($old)))->encrypt('kept');
        $current = Key::generate();
        $encrypter = new Encrypter(new KeyRing($current, $old));

        self::assertSame('kept', $encrypter->decrypt($payload));
        self::assertTrue($encrypter->needsReEncryption($payload));

        $rotated = $encrypter->reEncrypt($payload);
        self::assertStringStartsWith('v1.' . $current->id . '.', $rotated);
        self::assertSame('kept', $encrypter->decrypt($rotated));
    }

    public function testAPayloadFromBeforeKeyIdsDecryptsWithAnyKeyInTheRing(): void
    {
        $old = Key::generate();
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $legacy = sodium_bin2base64($nonce . sodium_crypto_secretbox('legacy', $nonce, base64_decode($old->toBase64())), SODIUM_BASE64_VARIANT_ORIGINAL);

        $encrypter = new Encrypter(new KeyRing(Key::generate(), $old));

        self::assertSame('legacy', $encrypter->decrypt($legacy));
        self::assertTrue($encrypter->needsReEncryption($legacy));
    }

    /** @return iterable<string, array{callable(string): string}> */
    public static function tamperings(): iterable
    {
        yield 'unknown key id' => [static fn (string $p): string => preg_replace('/^v1\.[0-9a-f]+\./', 'v1.00000000.', $p) ?? $p];
        yield 'changed body' => [static fn (string $p): string => substr($p, 0, -4) . 'AAAA'];
        yield 'not base64' => [static fn (string $p): string => 'v1.' . explode('.', $p)[1] . '.***'];
        yield 'too short' => [static fn (string $p): string => 'v1.' . explode('.', $p)[1] . '.AAAA'];
        yield 'garbage' => [static fn (string $p): string => 'nonsense'];
    }

    /** @param callable(string): string $tamper */
    #[PHPUnit\Framework\Attributes\DataProvider('tamperings')]
    public function testATamperedPayloadIsADecryptException(callable $tamper): void
    {
        $encrypter = new Encrypter(new KeyRing(Key::generate()));

        $this->expectException(DecryptException::class);

        $encrypter->decrypt($tamper($encrypter->encrypt('secret')));
    }

    public function testKeysAreReadFromBase64WithOrWithoutAPrefix(): void
    {
        $base64 = base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));

        self::assertSame(Key::fromBase64($base64)->id, Key::fromBase64('base64:' . $base64)->id);
        self::assertSame(8, strlen(Key::fromBase64($base64)->id));
        self::assertSame($base64, Key::fromBase64($base64)->toBase64());
    }

    public function testAKeyOfTheWrongLengthIsRefused(): void
    {
        $this->expectException(InvalidKeyException::class);

        Key::fromBase64(base64_encode('short'));
    }

    public function testGenerateKeyReturnsAUsableBase64Key(): void
    {
        $encrypter = new Encrypter(new KeyRing(Key::fromBase64(Encrypter::generateKey())));

        self::assertSame('ok', $encrypter->decrypt($encrypter->encrypt('ok')));
    }

    public function testARingRefusesTheSameKeyTwice(): void
    {
        $key = Key::generate();

        $this->expectException(InvalidKeyException::class);

        new KeyRing($key, Key::fromBase64($key->toBase64()));
    }
}
