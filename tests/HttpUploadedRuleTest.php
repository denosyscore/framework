<?php

declare(strict_types=1);

namespace Denosys\Validation\Tests;

use Denosys\Validation\Rules\File;
use Denosys\Validation\Rules\HttpUploaded;
use Denosys\Validation\Validator;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

final class HttpUploadedRuleTest extends TestCase
{
    protected function setUp(): void
    {
        Validator::extend('file', File::class);
        Validator::extend('http_uploaded', HttpUploaded::class);
    }

    protected function tearDown(): void
    {
        Validator::reset();
    }

    public function testLocalPsrUploadPassesFileButFailsHttpUploadRule(): void
    {
        $upload = $this->upload('/tmp/local-file', UPLOAD_ERR_OK, 10);

        self::assertTrue(Validator::make(['document' => $upload], ['document' => 'file'])->validate());
        $validator = Validator::make(['document' => $upload], ['document' => 'file|http_uploaded']);
        self::assertFalse($validator->validate());
        self::assertTrue($validator->errors()->has('document'));
    }

    public function testRuleRejectsMissingStreamPathAndInvalidUploadStatus(): void
    {
        $rule = new HttpUploaded();
        self::assertFalse($rule->validate('document', $this->upload(null, UPLOAD_ERR_OK, 10)));
        self::assertFalse($rule->validate('document', $this->upload('/tmp/local-file', UPLOAD_ERR_PARTIAL, 10)));
        self::assertFalse($rule->validate('document', $this->upload('/tmp/local-file', UPLOAD_ERR_OK, 0)));
        self::assertFalse($rule->validate('document', 'not a file'));
    }

    public function testUploadProvenanceIsCheckedOnlyForValidNonemptyUploads(): void
    {
        $rule = new class extends HttpUploaded {
            public ?string $checkedPath = null;

            protected function isHttpUpload(string $path): bool
            {
                $this->checkedPath = $path;

                return true;
            }
        };

        self::assertTrue($rule->validate('document', $this->upload('/tmp/upload-path', UPLOAD_ERR_OK, 10)));
        self::assertSame('/tmp/upload-path', $rule->checkedPath);
        $rule->checkedPath = null;
        self::assertFalse($rule->validate('document', $this->upload('/tmp/upload-path', UPLOAD_ERR_PARTIAL, 10)));
        self::assertNull($rule->checkedPath);
    }

    private function upload(?string $path, int $error, int $size): UploadedFileInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getMetadata')->with('uri')->willReturn($path);
        $file = $this->createMock(UploadedFileInterface::class);
        $file->method('getError')->willReturn($error);
        $file->method('getSize')->willReturn($size);
        $file->method('getStream')->willReturn($stream);

        return $file;
    }
}
