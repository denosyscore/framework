<?php

declare(strict_types=1);

namespace Denosys\Validation\Rules;

use Psr\Http\Message\UploadedFileInterface;
use SplFileInfo;

/**
 * File validation rule - ensures value is an uploaded file
 */
class File implements RuleInterface
{
    /**
     * @param array<int|string, mixed> $parameters
     * @param array<string, mixed> $data
     */
    public function validate(string $field, mixed $value, array $parameters = [], array $data = []): bool
    {
        // Accept PSR-7 UploadedFileInterface
        if ($value instanceof UploadedFileInterface) {
            // Check if file was actually uploaded (not empty)
            return $value->getError() === UPLOAD_ERR_OK && $value->getSize() > 0;
        }

        // Accept SplFileInfo
        if ($value instanceof SplFileInfo) {
            return $value->isFile();
        }

        return false;
    }

    /**
     * @param array<int|string, mixed> $parameters
     */
    public function message(string $field, array $parameters = []): string
    {
        return "The :attribute must be a file.";
    }

    /**
     * @return array<int, string>
     */
    public static function parameterNames(): array
    {
        return [];
    }

    public static function ruleName(): string
    {
        return 'file';
    }
}
