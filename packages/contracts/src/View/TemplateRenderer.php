<?php

declare(strict_types=1);

namespace Denosys\Contracts\View;

/**
 * Render a named template to a string.
 *
 * Code that only needs rendered output (an HTTP response factory, a mailer)
 * type-hints this instead of a particular template engine.
 */
interface TemplateRenderer
{
    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): string;
}
