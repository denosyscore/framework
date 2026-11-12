<?php

declare(strict_types=1);

use Denosys\Config\ConfigLoader;
use Denosys\Config\Repository;
use Denosys\Mail\MailTransportFactory;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

function configurationWithFiles(array $files): Repository
{
    $directory = sys_get_temp_dir() . '/denosys-config-null-' . bin2hex(random_bytes(4));
    mkdir($directory);
    foreach ($files as $name => $values) {
        file_put_contents("{$directory}/{$name}.php", '<?php return ' . var_export($values, true) . ';');
    }

    $configuration = (new ConfigLoader())->fromDirectory($directory);

    return $configuration;
}

it('keeps SMTP defaults when mail settings are stored as null', function () {
    // Typical when config files read environment variables that are not set.
    $config = configurationWithFiles(['mail' => [
        'default' => null,
        'dsn' => null,
        'mailers' => ['smtp' => ['host' => null, 'port' => null, 'encryption' => null, 'username' => null, 'password' => null]],
    ]]);

    $transport = (new MailTransportFactory())->create($config);

    expect($transport)->toBeInstanceOf(EsmtpTransport::class)
        ->and((string) $transport)->toBe('smtp://localhost:587')
        ->and($transport->getStream()->getPort())->toBe(587)
        ->and($transport->isTlsRequired())->toBeTrue();
});

it('traverses array-accessible configuration values', function () {
    $directory = sys_get_temp_dir() . '/denosys-config-object-' . bin2hex(random_bytes(4));
    mkdir($directory);
    file_put_contents("{$directory}/app.php", '<?php return ["options" => new ArrayObject(["mode" => "strict", "empty" => null])];');
    $config = (new ConfigLoader())->fromDirectory($directory);

    expect($config->get('app.options.mode', 'fallback'))->toBe('strict')
        ->and($config->has('app.options.mode'))->toBeTrue()
        ->and($config->has('app.options.empty'))->toBeTrue()
        ->and($config->get('app.options.empty', 'fallback'))->toBeNull()
        ->and($config->has('app.options.missing'))->toBeFalse()
        ->and($config->get('app.options.missing', 'fallback'))->toBe('fallback');
});
