<?php

declare(strict_types=1);

use Denosys\Contracts\Config\ConfigReader;
use Denosys\Filesystem\Exceptions\FileNotFoundException;
use Denosys\Filesystem\Exceptions\FilesystemException;
use Denosys\Filesystem\Exceptions\UnsafeFileType;
use Denosys\Filesystem\Exceptions\UnsupportedOperation;
use Denosys\Filesystem\FilesystemInterface;
use Denosys\Filesystem\FilesystemManager;
use GuzzleHttp\Psr7\UploadedFile;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;

final class FilesystemTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/denosys-filesystem-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->root)) {
            return;
        }
        $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->root);
    }

    /** @param array<string, mixed> $disk */
    private function disk(array $disk = []): FilesystemInterface
    {
        $config = new class (['driver' => 'local', 'root' => $this->root] + $disk) implements ConfigReader {
            /** @param array<string, mixed> $disk */
            public function __construct(private array $disk)
            {
            }

            public function get(string $key, mixed $default = null): mixed
            {
                return match ($key) {
                    'filesystems.default' => 'local',
                    'filesystems.disks.local' => $this->disk,
                    default => $default,
                };
            }
        };

        return (new FilesystemManager($config))->disk();
    }

    private function upload(string $contents, string $clientName): UploadedFile
    {
        return new UploadedFile(Utils::streamFor($contents), strlen($contents), UPLOAD_ERR_OK, $clientName, 'application/octet-stream');
    }

    public function testFailedOperationsThrowWithTheirCause(): void
    {
        $disk = $this->disk();

        foreach ([
            'copy' => static fn () => $disk->copy('missing.txt', 'copy.txt'),
            'move' => static fn () => $disk->move('missing.txt', 'moved.txt'),
            'size' => static fn () => $disk->size('missing.txt'),
            'lastModified' => static fn () => $disk->lastModified('missing.txt'),
            'mimeType' => static fn () => $disk->mimeType('missing.txt'),
        ] as $operation => $call) {
            try {
                $call();
                self::fail("{$operation} did not throw");
            } catch (FilesystemException $e) {
                self::assertInstanceOf(Throwable::class, $e->getPrevious(), $operation);
            }
        }
    }

    public function testReadingAMissingFileIsNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->disk()->get('missing.txt');
    }

    public function testALocalDiskCannotMakeTemporaryUrls(): void
    {
        $this->expectException(UnsupportedOperation::class);

        $this->disk(['url' => 'https://example.test/files'])->temporaryUrl('a.txt', new DateTimeImmutable('+5 minutes'));
    }

    public function testAnUploadIsNamedByItsDetectedType(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGNgYGD4DwABBAEAcCBlCwAAAABJRU5ErkJggg==');

        $stored = $this->disk()->putFile('uploads', $this->upload($png, 'photo.php'));

        self::assertMatchesRegularExpression('#^uploads/[0-9a-f]{40}\.png$#', $stored);
    }

    public function testPhpContentIsRefusedWhateverItsName(): void
    {
        $this->expectException(UnsafeFileType::class);

        $this->disk()->putFile('uploads', $this->upload("<?php echo 'hi';\n", 'photo.jpg'));
    }

    public function testAnExecutableNameIsRefused(): void
    {
        $this->expectException(UnsafeFileType::class);

        $this->disk()->putFileAs('uploads', $this->upload('plain text', 'notes.txt'), 'shell.PHP');
    }

    public function testPrivateFilesAreReadableOnlyByTheOwner(): void
    {
        $disk = $this->disk(['visibility' => 'private']);

        $disk->put('secret.txt', 'x');

        clearstatcache();

        self::assertSame('0600', substr(sprintf('%o', fileperms($this->root . '/secret.txt')), -4));
    }

    public function testS3ClientOptionsPassThrough(): void
    {
        $options = FilesystemManager::s3ClientOptions([
            'key' => 'k',
            'secret' => 's',
            'region' => 'auto',
            'endpoint' => 'https://storage.example.test',
            'use_path_style_endpoint' => true,
            'options' => ['http' => ['timeout' => 5]],
        ]);

        self::assertSame('https://storage.example.test', $options['endpoint']);
        self::assertTrue($options['use_path_style_endpoint']);
        self::assertSame(['timeout' => 5], $options['http']);
        self::assertSame('auto', $options['region']);
    }

    public function testTheManagerDoesNotForwardUnknownCalls(): void
    {
        self::assertFalse(method_exists(FilesystemManager::class, '__call'));
    }

    public function testXmlThatCanCarryScriptIsRefused(): void
    {
        $this->expectException(UnsafeFileType::class);

        $this->disk()->putFile('uploads', $this->upload('<?xml version="1.0"?><html xmlns="http://www.w3.org/1999/xhtml"><script>alert(1)</script></html>', 'a.txt'));
    }

    /** @return iterable<string, array{string}> */
    public static function unsafeNames(): iterable
    {
        yield 'trailing dot' => ['a.php.'];
        yield 'trailing space' => ['a.php '];
        yield 'double extension' => ['a.php.jpg'];
        yield 'user ini' => ['.user.ini'];
        yield 'xml' => ['a.xhtml'];
        yield 'dot component' => ['.htaccess/.'];
        yield 'path separator' => ['sub/a.txt'];
        yield 'backslash' => ['..\\a.txt'];
        yield 'parent' => ['..'];
    }

    #[PHPUnit\Framework\Attributes\DataProvider('unsafeNames')]
    public function testDisguisedExecutableNamesAreRefused(string $name): void
    {
        $this->expectException(UnsafeFileType::class);

        $this->disk()->putFileAs('uploads', $this->upload('plain text', 'notes.txt'), $name);
    }

    public function testAPublicDiskMakesReadableDirectories(): void
    {
        $disk = $this->disk(['visibility' => 'public']);

        $disk->put('nested/file.txt', 'x');

        clearstatcache();

        self::assertSame('0755', substr(sprintf('%o', fileperms($this->root . '/nested')), -4));
        self::assertSame('0644', substr(sprintf('%o', fileperms($this->root . '/nested/file.txt')), -4));
    }

    public function testANonSeekableUploadIsStoredWhole(): void
    {
        $contents = str_repeat('a', 20000);
        $pipe = popen(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg('echo str_repeat("a", 20000);'), 'r');
        self::assertIsResource($pipe);
        $stream = Utils::streamFor($pipe);
        self::assertFalse($stream->isSeekable());
        $upload = new UploadedFile($stream, null, UPLOAD_ERR_OK, 'a.txt', 'text/plain');
        $disk = $this->disk();

        $stored = $disk->putFile('uploads', $upload);

        self::assertSame($contents, $disk->get($stored));
    }

    public function testPrivateDirectoriesOnAPublicDiskStayPrivate(): void
    {
        $disk = $this->disk(['visibility' => 'public', 'directory_visibility' => 'private']);

        $disk->makeDirectory('restricted');

        clearstatcache();

        self::assertSame('0700', substr(sprintf('%o', fileperms($this->root . '/restricted')), -4));
    }
}
