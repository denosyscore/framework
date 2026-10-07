<?php

declare(strict_types=1);

namespace Denosys\Routing\ParameterResolvers;

use Denosys\Container\ContainerInterface;
use Denosys\Http\FormRequest;
use Denosys\Http\Request;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Bridges framework HTTP form requests into route handler invocation.
 */
final readonly class FormRequestResolver implements ParameterResolverInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function canResolve(ReflectionParameter $parameter, array $routeArguments): bool
    {
        $type = $parameter->getType();

        return $type instanceof ReflectionNamedType
            && !$type->isBuiltin()
            && is_subclass_of($type->getName(), FormRequest::class);
    }

    public function resolve(
        ReflectionParameter $parameter,
        ServerRequestInterface $request,
        array $routeArguments
    ): FormRequest {
        $type = $parameter->getType();
        if (!$type instanceof ReflectionNamedType || !$this->canResolve($parameter, $routeArguments)) {
            throw new \InvalidArgumentException('The handler parameter must be a FormRequest subclass.');
        }

        /** @var class-string<FormRequest> $requestClass */
        $requestClass = $type->getName();
        $formRequest = new $requestClass(Request::createFromPsr7($request));
        $formRequest->setContainer($this->container);
        $formRequest->validate();

        return $formRequest;
    }

    public function runsBefore(): array
    {
        return [TypeBasedResolver::class];
    }

    public function runsAfter(): array
    {
        return [];
    }
}
