<?php

declare(strict_types=1);

namespace Tests\Integration;

use Denosys\Auth\Authentication\Authenticator;
use Denosys\Framework\Providers\AuthServiceProvider;
use Denosys\Auth\Authentication\UserProviderInterface;
use Denosys\Auth\Identity\AuthenticatableInterface;
use Denosys\Auth\Identity\Identity;
use Denosys\Auth\Middleware\RememberCookieMiddleware;
use Denosys\Auth\Middleware\RememberCookieOptions;
use Denosys\Encryption\Encrypter;
use Denosys\Encryption\Key;
use Denosys\Encryption\KeyRing;
use Denosys\Encryption\EncrypterInterface;
use Denosys\Container\Container;
use Denosys\Session\SessionInterface;
use InvalidArgumentException;
use Denosys\Session\Handlers\ArraySessionHandler;
use Denosys\Session\Store;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** The framework's auth provider wires the opt-in remember-cookie middleware. */
final class AuthServiceProviderTest extends TestCase
{
    public function testServiceProviderBindsOptInMiddlewareWithoutEnablingItGlobally(): void
    {
        $container = new Container();
        $container->instance('config', new class {
            public function get(string $key, mixed $default = null): mixed
            {
                return $key === 'auth.remember.cookie_name' ? 'custom_remember' : $default;
            }
        });
        $session = $this->session();
        $user = new ProviderTestUser();
        $container->instance(SessionInterface::class, $session);
        $container->instance(EncrypterInterface::class, new Encrypter(new KeyRing(Key::generate())));

        (new AuthServiceProvider())->register($container);
        $container->instance(UserProviderInterface::class, new ProviderTestUserProvider($user));
        $guard = $container->get(Authenticator::class);
        $guard->login(Identity::fromAuthenticatable($user), $user);
        $session->put('_auth_remember_token', $user->getRememberToken());
        $session->put('_auth_remember_id', $user->getAuthIdentifier());

        $middleware = $container->get(RememberCookieMiddleware::class);
        self::assertInstanceOf(RememberCookieMiddleware::class, $middleware);
        $response = $middleware->process(new ServerRequest([], [], 'https://example.test/login', 'POST'), $this->handler());
        self::assertStringContainsString('custom_remember=', $response->getHeaderLine('Set-Cookie'));
    }

    private function session(): Store
    {
        $session = new Store('auth-test', new ArraySessionHandler());
        $session->start();

        return $session;
    }

    private function cookie(Encrypter $encrypter, ProviderTestUser $user, int $expires): string
    {
        $payload = json_encode([
            'id' => $user->getAuthIdentifier(),
            'token' => $user->getRememberToken(),
            'password' => sha1($user->getAuthPassword()),
            'expires' => $expires,
        ], JSON_THROW_ON_ERROR);

        return rtrim(strtr($encrypter->encrypt($payload), '+/', '-_'), '=');
    }

    private function handler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new HtmlResponse('ok');
            }
        };
    }

    private function logoutHandler(Authenticator $guard): RequestHandlerInterface
    {
        return new class ($guard) implements RequestHandlerInterface {
            public function __construct(private Authenticator $guard)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->guard->logout();

                return new HtmlResponse('ok');
            }
        };
    }
}

final class ProviderTestUser implements AuthenticatableInterface
{
    public string $passwordHash = 'stored-hash';
    private string $rememberToken;

    public function __construct()
    {
        $this->rememberToken = str_repeat('a', 64);
    }

    public function getAuthIdentifier(): string|int { return 7; }
    public function getAuthIdentifierName(): string { return 'id'; }
    public function getAuthPassword(): string { return $this->passwordHash; }
    public function getRememberToken(): ?string { return $this->rememberToken; }
    public function setRememberToken(string $token): void { $this->rememberToken = $token; }
    public function getRememberTokenName(): string { return 'remember_token'; }
    public function getAuthClaims(): array { return []; }
}

final class ProviderTestUserProvider implements UserProviderInterface
{
    public function __construct(private ProviderTestUser $user)
    {
    }

    public function findById(string|int $id): ?AuthenticatableInterface
    {
        return (int) $id === 7 ? $this->user : null;
    }

    public function findByCredential(string $field, string $value): ?AuthenticatableInterface
    {
        return null;
    }

    public function findByRememberToken(string|int $id, string $token): ?AuthenticatableInterface
    {
        return (int) $id === 7 && hash_equals($this->user->getRememberToken() ?? '', $token)
            ? $this->user
            : null;
    }

    public function updateRememberToken(AuthenticatableInterface $user, string $token): void
    {
        $user->setRememberToken($token);
    }

    public function validatePassword(AuthenticatableInterface $user, string $password): bool
    {
        return false;
    }

    public function rehashPasswordIfRequired(AuthenticatableInterface $user, string $password): void
    {
    }
}
