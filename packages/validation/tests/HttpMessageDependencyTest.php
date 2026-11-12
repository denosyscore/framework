<?php

declare(strict_types=1);

namespace Denosys\Validation\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

final class HttpMessageDependencyTest extends TestCase
{
    public function testInterfacesUsedByValidationAreInstalledDirectly(): void
    {
        self::assertTrue(interface_exists(ResponseInterface::class));
        self::assertTrue(interface_exists(StreamInterface::class));
        self::assertTrue(interface_exists(UploadedFileInterface::class));
    }
}
