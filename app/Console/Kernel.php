<?php

declare(strict_types=1);

namespace App\Console;

use Denosys\Console\Kernel as ConsoleKernel;

/**
 * Application Console Kernel.
 * 
 * Commands in app/Console/Commands are auto-discovered!
 * Just create a command file - no registration needed.
 * 
 * To create a new command:
 *   php cfxp make:command YourCommandName
 */
class Kernel extends ConsoleKernel
{
    // Commands are auto-discovered from app/Console/Commands
    // 
    // For commands in other locations, add them here:
    // protected array $commands = [
    //     \Other\Namespace\SomeCommand::class,
    // ];
}
