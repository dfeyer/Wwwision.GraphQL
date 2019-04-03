<?php

namespace Wwwision\GraphQL;

use Closure;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\TypeInfo;
use Neos\Flow\Annotations as Flow;

/**
 * A type resolver (aka factory) for GraphQL type definitions.
 * This class is required in order to prevent multiple instantiation of the same type and to allow types to reference themselves
 *
 * Usage:
 *
 * Type::nonNull($typeResolver->get(SomeClass::class))
 *
 * @Flow\Scope("singleton")
 */
class TypeResolver
{
    /**
     * @param string $typeClassName
     * @param Closure $wrappers
     * @return ObjectType
     */
    public function get($typeClassName, Closure $wrappers = null)
    {
        return WrappedType::create($typeClassName, $this, $wrappers);
    }
}
