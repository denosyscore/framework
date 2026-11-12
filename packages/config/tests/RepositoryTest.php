<?php

declare(strict_types=1);

namespace Denosys\Config\Tests;

use Denosys\Config\ConfigException;
use Denosys\Config\ConfigSection;
use Denosys\Config\Repository;
use PHPUnit\Framework\TestCase;

final class RepositoryTest extends TestCase
{
    private function repository(): Repository
    {
        return new Repository([
            'app' => ['name' => 'Shop', 'debug' => null, 'port' => 8080, 'secure' => true],
            'mail' => ['from' => ['address' => 'shop@example.test', 'name' => 'Shop']],
            'list' => ['a', 'b'],
        ]);
    }

    public function testReadsNestedPaths(): void
    {
        self::assertSame('Shop', $this->repository()->get('app.name'));
        self::assertSame('shop@example.test', $this->repository()->get('mail.from.address'));
        self::assertSame(['a', 'b'], $this->repository()->get('list'));
    }

    public function testHasSeesExistingKeysIncludingNull(): void
    {
        self::assertTrue($this->repository()->has('app.name'));
        self::assertTrue($this->repository()->has('app.debug'));
        self::assertFalse($this->repository()->has('app.missing'));
        self::assertFalse($this->repository()->has('app.name.deeper'));
    }

    public function testStoredNullIsReturnedRatherThanTheDefault(): void
    {
        self::assertNull($this->repository()->get('app.debug', 'fallback'));
    }

    public function testDefaultIsNeverIndexedIntoForMissingPaths(): void
    {
        self::assertSame(['k' => 'default'], $this->repository()->get('missing.k', ['k' => 'default']));
        self::assertSame('fallback', $this->repository()->get('missing.k', 'fallback'));
    }

    public function testTypedReadersReturnTheValue(): void
    {
        self::assertSame('Shop', $this->repository()->string('app.name'));
        self::assertSame(8080, $this->repository()->int('app.port'));
        self::assertTrue($this->repository()->bool('app.secure'));
        self::assertSame(['a', 'b'], $this->repository()->array('list'));
    }

    public function testTypedReadersRejectMissingAndWrongTypes(): void
    {
        try {
            $this->repository()->string('app.port');
            self::fail('A non-string value was accepted.');
        } catch (ConfigException $exception) {
            self::assertStringContainsString('app.port', $exception->getMessage());
            self::assertStringContainsString('string', $exception->getMessage());
        }

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('app.missing');
        $this->repository()->int('app.missing');
    }

    public function testTypedReadersUseADefaultOnlyWhenTheKeyIsMissing(): void
    {
        self::assertSame('fallback', $this->repository()->string('app.missing', 'fallback'));
        self::assertSame(3, $this->repository()->int('app.missing', 3));

        $this->expectException(ConfigException::class);
        $this->repository()->string('app.debug', 'fallback');
    }

    public function testBuildsTypedSections(): void
    {
        $mail = $this->repository()->section(MailSection::class);

        self::assertSame('shop@example.test', $mail->address);
        self::assertSame('Shop', $mail->name);
    }

    public function testExposesEverything(): void
    {
        self::assertSame(['a', 'b'], $this->repository()->all()['list']);
    }
}

final readonly class MailSection implements ConfigSection
{
    public function __construct(public string $address, public string $name)
    {
    }

    public static function from(Repository $config): static
    {
        return new static($config->string('mail.from.address'), $config->string('mail.from.name'));
    }
}
