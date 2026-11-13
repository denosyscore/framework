<?php

declare(strict_types=1);

namespace Denosys\Framework\Routing\ParameterResolvers;

use Denosys\Database\Exceptions\RecordNotFound;
use Denosys\Database\Record\Record;
use Denosys\Framework\Routing\Contracts\UrlRoutable;
use Denosys\Routing\Exceptions\NotFoundException;
use Denosys\Routing\ParameterResolvers\ParameterResolverInterface;
use Denosys\Routing\ParameterResolvers\TypeBasedResolver;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

/**
 * Loads a controller parameter typed as a Record from the route argument
 * of the same name: `{user}` and `User $user` load User::findOrFail(). A
 * missing record is a 404. A class implementing UrlRoutable resolves
 * itself instead.
 *
 * Runs before TypeBasedResolver, which would otherwise build an empty record.
 */
final readonly class RecordBindingResolver implements ParameterResolverInterface
{
    /** @param array<string, mixed> $routeArguments */
    public function canResolve(ReflectionParameter $parameter, array $routeArguments): bool
    {
        return $this->bindableType($parameter->getType()) !== null
            && array_key_exists($parameter->getName(), $routeArguments);
    }

    /** @param array<string, mixed> $routeArguments */
    public function resolve(ReflectionParameter $parameter, ServerRequestInterface $request, array $routeArguments): mixed
    {
        $type = $this->bindableType($parameter->getType());
        $value = $routeArguments[$parameter->getName()] ?? null;
        if ($type === null) {
            return null;
        }

        if (!is_int($value) && !is_string($value)) {
            throw new NotFoundException();
        }

        if (is_a($type, UrlRoutable::class, true)) {
            return $type::resolveRouteBinding($value);
        }

        try {
            return $type::findOrFail($value);
        } catch (RecordNotFound $e) {
            throw new NotFoundException(previous: $e);
        }
    }

    public function runsBefore(): array
    {
        return [TypeBasedResolver::class];
    }

    public function runsAfter(): array
    {
        return [];
    }

    /** @return class-string<Record>|class-string<UrlRoutable>|null */
    private function bindableType(?ReflectionType $type): ?string
    {
        $types = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];
        foreach ($types as $named) {
            if (!$named instanceof ReflectionNamedType || $named->isBuiltin()) {
                continue;
            }

            $name = $named->getName();
            if (is_a($name, UrlRoutable::class, true) || is_a($name, Record::class, true)) {
                return $name;
            }
        }

        return null;
    }
}
