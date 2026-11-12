<?php

declare(strict_types=1);

namespace Denosys\Config\Tests;

use Denosys\Config\Environment;
use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\RepositoryBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EnvironmentTest extends TestCase
{
    private function environment(array $values): Environment
    {
        $repository = RepositoryBuilder::createWithNoAdapters()->addAdapter(ArrayAdapter::class)->make();
        foreach ($values as $key => $value) {
            $repository->set($key, $value);
        }

        return new Environment($repository);
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function casts(): iterable
    {
        yield 'true' => ['true', true];
        yield '(true)' => ['(true)', true];
        yield 'false' => ['FALSE', false];
        yield 'empty' => ['empty', ''];
        yield 'null' => ['null', null];
        yield 'int' => ['(int)42', 42];
        yield 'float' => ['(float)1.5', 1.5];
        yield 'plain' => ['hello', 'hello'];
        yield 'numeric stays a string' => ['8080', '8080'];
    }

    #[DataProvider('casts')]
    public function testCastsValuesOneWay(string $raw, mixed $expected): void
    {
        self::assertSame($expected, $this->environment(['VALUE' => $raw])->get('VALUE'));
    }

    public function testEachCallUsesItsOwnDefault(): void
    {
        $environment = $this->environment([]);

        self::assertSame('a', $environment->get('MISSING', 'a'));
        self::assertSame('b', $environment->get('MISSING', 'b'));
    }

    public function testNullValueIsReturnedRatherThanTheDefault(): void
    {
        self::assertNull($this->environment(['VALUE' => 'null'])->get('VALUE', 'fallback'));
    }

    public function testLoadsDotenvFilesThatExist(): void
    {
        $directory = sys_get_temp_dir() . '/denosys-env-' . bin2hex(random_bytes(6));
        mkdir($directory, 0700, true);
        file_put_contents($directory . '/.env', "DENOSYS_ENV_PROBE=loaded\n");
        $environment = $this->environment([]);

        try {
            $environment->load($directory, ['.env', '.env.missing']);
            self::assertSame('loaded', $environment->get('DENOSYS_ENV_PROBE'));
        } finally {
            unlink($directory . '/.env');
            rmdir($directory);
        }
    }
}
