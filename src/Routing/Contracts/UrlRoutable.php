<?php

declare(strict_types=1);

namespace Denosys\Routing\Contracts;

/**
 * Interface for models that support route model binding.
 * 
 * Models implementing this interface can be automatically resolved
 * from route parameters in controller method signatures.
 */
interface UrlRoutable
{
    /**
     * Get the route key name used for binding.
     * 
     * By default, models use 'id', but this can be overridden
     * to use alternative columns like 'slug' or 'uuid'.
     *
     * @return string The column name to use for route binding
     */
    public function getRouteKeyName(): string;

    /**
     * Resolve the model instance from a route parameter value.
     * 
     * This method is called during route model binding to find the
     * model instance matching the given route parameter.
     *
     * @param string|int $value The route parameter value
     * @return static The resolved model instance
     * @throws \Denosys\Database\Exceptions\ModelNotFoundException When the model cannot be found
     */
    public static function resolveRouteBinding(string|int $value): static;
}
