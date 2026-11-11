<?php

declare(strict_types=1);

namespace Denosys\Validation;

/**
 * The rules this package ships, by name.
 *
 * The validator falls back to this map, so rules work without a service
 * provider or container. Rules registered with Validator::extend() take
 * precedence over these.
 */
final class BuiltInRules
{
    /** @var array<string, class-string<Rules\RuleInterface>> */
    public const MAP = [
        'after'         => Rules\After::class,
        'alpha'         => Rules\Alpha::class,
        'alpha_num'     => Rules\AlphaNum::class,
        'array'         => Rules\ArrayRule::class,
        'before'        => Rules\Before::class,
        'between'       => Rules\Between::class,
        'boolean'       => Rules\Boolean::class,
        'confirmed'     => Rules\Confirmed::class,
        'date'          => Rules\Date::class,
        'different'     => Rules\Different::class,
        'dimensions'    => Rules\Dimensions::class,
        'email'         => Rules\Email::class,
        'enum'          => Rules\Enum::class,
        'exists'        => Rules\Exists::class,
        'file'          => Rules\File::class,
        'filled'        => Rules\Filled::class,
        'http_uploaded' => Rules\HttpUploaded::class,
        'in'            => Rules\In::class,
        'integer'       => Rules\Integer::class,
        'ip_address'    => Rules\IpAddress::class,
        'json'          => Rules\Json::class,
        'max'           => Rules\Max::class,
        'mimes'         => Rules\Mimes::class,
        'min'           => Rules\Min::class,
        'nullable'      => Rules\Nullable::class,
        'numeric'       => Rules\Numeric::class,
        'present'       => Rules\Present::class,
        'regex'         => Rules\Regex::class,
        'required'      => Rules\Required::class,
        'same'          => Rules\Same::class,
        'size'          => Rules\Size::class,
        'sometimes'     => Rules\Sometimes::class,
        'string'        => Rules\StringRule::class,
        'unique'        => Rules\Unique::class,
        'url'           => Rules\Url::class,
        'uuid'          => Rules\Uuid::class,
    ];
}
