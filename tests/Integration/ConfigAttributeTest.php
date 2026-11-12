<?php

declare(strict_types=1);

use Denosys\Application;
use Denosys\Container\Attributes\Config;

final readonly class GreetsByAppName
{
    public function __construct(
        #[Config('app.name')] public string $name,
        #[Config('app.missing')] public string $fallback = 'default',
    ) {}
}

it('injects application configuration into constructors with #[Config]', function (): void {
    $previous = Application::getInstance();
    $base = sys_get_temp_dir() . '/denosys-config-attribute-' . bin2hex(random_bytes(6));
    mkdir($base . '/config', 0700, true);
    file_put_contents($base . '/config/app.php', "<?php return ['name' => 'Shop'];");

    try {
        $service = (new Application($base))->getContainer()->get(GreetsByAppName::class);

        expect($service->name)->toBe('Shop')
            ->and($service->fallback)->toBe('default');
    } finally {
        $previous === null ? Application::resetInstance() : Application::setInstance($previous);
        unlink($base . '/config/app.php');
        rmdir($base . '/config');
        rmdir($base);
    }
});
