<?php

declare(strict_types=1);

namespace Denosys\Validation\Rules;

use Psr\Http\Message\UploadedFileInterface;
use SplFileInfo;

/**
 * MIME types validation rule
 * 
 * Usage:
 *   String syntax: 'mimes:jpg,png,pdf'
 *   Object syntax: new Mimes('jpg', 'png', 'pdf')
 */
class Mimes implements RuleInterface
{
    /** @var array<string>|null */
    private ?array $allowedExtensions = null;

    /**
     * @param string ...$extensions Allowed file extensions
     */
    public function __construct(string ...$extensions)
    {
        if (count($extensions) > 0) {
            $this->allowedExtensions = $extensions;
        }
    }

    /**
     * @var array<string, array<string>>
     */
    private array $mimeTypes = [
        'jpg' => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'svg' => ['image/svg+xml'],
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
        'mp4' => ['video/mp4'],
        'mp3' => ['audio/mpeg'],
        'txt' => ['text/plain'],
        'csv' => ['text/csv', 'text/plain'],
    ];

    /**
     * @param array<int|string, mixed> $parameters
     * @param array<string, mixed> $data
     */
    public function validate(string $field, mixed $value, array $parameters = [], array $data = []): bool
    {
        // Use constructor-provided extensions first, then fall back to parameters
        $allowedExtensions = $this->allowedExtensions ?? $parameters['mimes'] ?? $parameters;
        
        // If it's a comma-separated string, split it
        if (is_string($allowedExtensions)) {
            $allowedExtensions = array_map('trim', explode(',', $allowedExtensions));
        }
        
        if (!is_array($allowedExtensions)) {
            return false;
        }
        
        // Get MIME type from file
        $mimeType = $this->getMimeType($value);
        
        if ($mimeType === null) {
            return false;
        }

        foreach ($allowedExtensions as $extension) {
            $extension = strtolower(trim($extension));

            if (isset($this->mimeTypes[$extension]) && in_array($mimeType, $this->mimeTypes[$extension], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get MIME type from various file types
     */
    private function getMimeType(mixed $value): ?string
    {
        // PSR-7 UploadedFileInterface
        if ($value instanceof UploadedFileInterface) {
            // First try the client-provided MIME type
            $clientMime = $value->getClientMediaType();
            
            // For better security, try to detect from stream if possible
            $stream = $value->getStream();
            $stream->rewind();
            $content = $stream->read(1024);
            $stream->rewind();
            
            // Try to detect from content using finfo
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $detected = $finfo->buffer($content);
            
            return $detected ?: $clientMime;
        }

        // SplFileInfo
        if ($value instanceof SplFileInfo) {
            $pathname = $value->getPathname();
            if (file_exists($pathname)) {
                return mime_content_type($pathname) ?: null;
            }
        }

        return null;
    }

    /**
     * @param array<int|string, mixed> $parameters
     */
    public function message(string $field, array $parameters = []): string
    {
        $mimes = $parameters['mimes'] ?? $parameters;
        
        if (is_string($mimes)) {
            $list = $mimes;
        } elseif (is_array($mimes)) {
            $list = implode(', ', $mimes);
        } else {
            $list = '';
        }

        return "The :attribute must be a file of type: {$list}.";
    }

    /**
     * @return array<int, string>
     */
    public static function parameterNames(): array
    {
        return ['mimes'];
    }

    public static function ruleName(): string
    {
        return 'mimes';
    }
}
