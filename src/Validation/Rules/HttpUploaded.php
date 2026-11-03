<?php

declare(strict_types=1);

namespace Denosys\Validation\Rules;

use Psr\Http\Message\UploadedFileInterface;

/** Require a successful upload supplied by PHP's HTTP upload handling. */
class HttpUploaded implements RuleInterface
{
    /** @param array<int, string> $parameters @param array<string, mixed> $data */
    public function validate(string $field, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (!$value instanceof UploadedFileInterface
            || $value->getError() !== UPLOAD_ERR_OK
            || $value->getSize() === null
            || $value->getSize() <= 0) {
            return false;
        }

        $path = $value->getStream()->getMetadata('uri');

        return is_string($path) && $path !== '' && $this->isHttpUpload($path);
    }

    protected function isHttpUpload(string $path): bool
    {
        return is_uploaded_file($path);
    }

    /** @param array<int, string> $parameters */
    public function message(string $field, array $parameters = []): string
    {
        return 'The :attribute must be an HTTP-uploaded file.';
    }

    /** @return array<int, string> */
    public static function parameterNames(): array
    {
        return [];
    }

    public static function ruleName(): string
    {
        return 'http_uploaded';
    }
}
