<?php

declare(strict_types=1);

use Denosys\Container\Container;
use Denosys\Http\Exceptions\AuthorizationException;
use Denosys\Http\FormRequest;
use Denosys\Routing\Strategy\ModelBindingInvocationStrategy;
use Denosys\Validation\Rules\Required;
use Denosys\Validation\ValidationException;
use Denosys\Validation\Validator;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;

final class DeniedInvocationFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        return false;
    }
}

final class RequiredInvocationFormRequest extends FormRequest
{
    public function rules(): array
    {
        return ['email' => 'required'];
    }
}

it('authorizes a typed form request before invoking its handler', function (): void {
    $strategy = new ModelBindingInvocationStrategy(new Container());
    $called = false;

    try {
        $strategy->invoke(
            static function (DeniedInvocationFormRequest $form) use (&$called): HtmlResponse {
                $called = true;

                return new HtmlResponse('unexpected');
            },
            new ServerRequest([], [], 'https://example.test/submit', 'POST'),
            [],
        );
        test()->fail('Authorization should prevent handler invocation.');
    } catch (AuthorizationException) {
        expect($called)->toBeFalse();
    }
});

it('validates a typed form request before invoking its handler', function (): void {
    Validator::extend('required', Required::class);
    $strategy = new ModelBindingInvocationStrategy(new Container());
    $called = false;

    try {
        $strategy->invoke(
            static function (RequiredInvocationFormRequest $form) use (&$called): HtmlResponse {
                $called = true;

                return new HtmlResponse('unexpected');
            },
            new ServerRequest([], [], 'https://example.test/submit', 'POST'),
            [],
        );
        test()->fail('Validation should prevent handler invocation.');
    } catch (ValidationException $exception) {
        expect($called)->toBeFalse();
        expect($exception->validator->errors()->has('email'))->toBeTrue();
    } finally {
        Validator::reset();
    }
});

it('uses the current request on repeated handler invocations', function (): void {
    Validator::extend('required', Required::class);
    $strategy = new ModelBindingInvocationStrategy(new Container());
    $handler = static fn (RequiredInvocationFormRequest $form): HtmlResponse =>
        new HtmlResponse((string) $form->validatedInput('email'));

    try {
        $first = $strategy->invoke(
            $handler,
            new ServerRequest([], [], 'https://example.test/submit', 'POST')
                ->withParsedBody(['email' => 'first@example.test']),
            [],
        );
        $second = $strategy->invoke(
            $handler,
            new ServerRequest([], [], 'https://example.test/submit', 'POST')
                ->withParsedBody(['email' => 'second@example.test']),
            [],
        );

        expect((string) $first->getBody())->toBe('first@example.test');
        expect((string) $second->getBody())->toBe('second@example.test');
    } finally {
        Validator::reset();
    }
});
