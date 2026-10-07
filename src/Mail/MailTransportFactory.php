<?php

declare(strict_types=1);

namespace Denosys\Mail;

use Denosys\Config\ConfigurationInterface;
use RuntimeException;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;

final class MailTransportFactory
{
    public function create(ConfigurationInterface $config): TransportInterface
    {
        $dsn = $config->get('mail.dsn');
        if (is_string($dsn) && $dsn !== '') {
            return Transport::fromDsn($dsn);
        }

        $driver = $config->get('mail.default', 'smtp');
        if ($driver === 'sendmail') {
            $command = (string) $config->get('mail.mailers.sendmail.path', '/usr/sbin/sendmail -bs');

            return Transport::fromDsn('sendmail://default?command=' . rawurlencode($command));
        }

        if ($driver !== 'smtp') {
            throw new RuntimeException('Unsupported mail transport. Configure mail.dsn for this driver.');
        }

        $host = (string) $config->get('mail.mailers.smtp.host', 'localhost');
        $port = (int) $config->get('mail.mailers.smtp.port', 587);
        $encryption = $config->get('mail.mailers.smtp.encryption', 'tls');
        if ($host === '' || $port < 1 || $port > 65535) {
            throw new RuntimeException('SMTP host and port must be configured.');
        }

        $scheme = $encryption === 'ssl' ? 'smtps' : 'smtp';
        $username = $config->get('mail.mailers.smtp.username');
        $password = $config->get('mail.mailers.smtp.password');
        $credentials = is_string($username) && $username !== ''
            ? rawurlencode($username) . ':' . rawurlencode(is_string($password) ? $password : '') . '@'
            : '';
        $options = $encryption === 'tls' ? '?require_tls=true' : '';

        return Transport::fromDsn("{$scheme}://{$credentials}{$host}:{$port}{$options}");
    }
}
