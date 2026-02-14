<?php

declare(strict_types=1);

namespace Denosys\Routing\ParameterResolvers;

use Denosys\Database\Model;
use Denosys\Routing\Contracts\UrlRoutable;
use Denosys\Routing\ParameterResolvers\ParameterResolverInterface;
use Denosys\Routing\ParameterResolvers\TypeBasedResolver;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

/**
 * Resolves route model binding for controller method parameters.
 * 
 * This resolver checks if a parameter type-hints a Model class or a class
 * implementing UrlRoutable, and if a matching route argument exists, it
 * automatically resolves the model instance from the database.
 * 
 * Uses runsBefore() to ensure this resolver runs BEFORE TypeBasedResolver,
 * which would otherwise instantiate an empty model.
 */
final readonly class RouteModelBindingResolver implements ParameterResolverInterface
{
    public function canResolve(ReflectionParameter $parameter, array $routeArguments): bool
    {
        $type = $parameter->getType();

        if ($type === null) {
            return false;
        }

        // Get the class type name
        $typeName = $this->getResolvableTypeName($type);

        if ($typeName === null) {
            return false;
        }

        // Check if it's a Model or implements UrlRoutable
        if (!$this->isBindableType($typeName)) {
            return false;
        }

        // Check if there's a matching route argument for this parameter
        $parameterName = $parameter->getName();

        return array_key_exists($parameterName, $routeArguments);
    }

    public function resolve(
        ReflectionParameter $parameter,
        ServerRequestInterface $request,
        array $routeArguments
    ): mixed {
        $parameterName = $parameter->getName();
        $value = $routeArguments[$parameterName];

        $typeName = $this->getResolvableTypeName($parameter->getType());

        if ($typeName === null) {
            return null;
        }

        // If the class implements UrlRoutable, use its custom resolution
        if (is_a($typeName, UrlRoutable::class, allow_string: true)) {
            return $typeName::resolveRouteBinding($value);
        }

        // For Model classes without UrlRoutable, use findOrFail
        if (is_subclass_of($typeName, Model::class)) {
            return $typeName::findOrFail($value);
        }

        return null;
    }

    /**
     * Run BEFORE TypeBasedResolver to intercept Model types before
     * they are instantiated as empty objects by the container.
     */
    public function runsBefore(): array
    {
        return [TypeBasedResolver::class];
    }

    public function runsAfter(): array
    {
        return [];
    }

    /**
     * Get the resolvable type name from a reflection type.
     */
    private function getResolvableTypeName(\ReflectionType|null $type): ?string
    {
        if ($type === null) {
            return null;
        }

        $types = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];

        foreach ($types as $namedType) {
            if (!$namedType instanceof ReflectionNamedType || $namedType->isBuiltin()) {
                continue;
            }

            $typeName = $namedType->getName();

            if ($this->isBindableType($typeName)) {
                return $typeName;
            }
        }

        return null;
    }

    /**
     * Check if the type is a Model or implements UrlRoutable.
     */
    private function isBindableType(string $typeName): bool
    {
        // Check if it's a Model subclass
        if (class_exists($typeName) && is_subclass_of($typeName, Model::class)) {
            return true;
        }

        // Check if it implements UrlRoutable
        if (class_exists($typeName) && is_a($typeName, UrlRoutable::class, allow_string: true)) {
            return true;
        }

        return false;
    }
}
