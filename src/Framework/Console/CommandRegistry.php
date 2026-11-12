<?php

declare(strict_types=1);

namespace Denosys\Framework\Console;

/**
 * Console commands contributed by providers. Each framework provider adds
 * the commands of the package it wires; the console kernel receives them.
 */
final class CommandRegistry
{
    /** @var list<class-string> */
    private array $commands = [];

    /**
     * @param class-string ...$commands
     */
    public function add(string ...$commands): void
    {
        foreach ($commands as $command) {
            if (!in_array($command, $this->commands, true)) {
                $this->commands[] = $command;
            }
        }
    }

    /**
     * @return list<class-string>
     */
    public function all(): array
    {
        return $this->commands;
    }
}
