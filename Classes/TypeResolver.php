<?php
declare(strict_types=1);

namespace Wwwision\GraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
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
     * @var ObjectType[]
     */
    private $types;

    /**
     * @param string|array $typeClassConfiguration
     * @param array $additionalArguments
     * @return ObjectType
     */
    public function get($typeClassConfiguration, ...$additionalArguments)
    {
        $originalTypeClassConfiguration = $typeClassConfiguration;
        if (is_array($typeClassConfiguration)) {
            $hash = md5(json_encode($typeClassConfiguration));
            $typeClassName = reset($typeClassConfiguration);
        } elseif (is_string($typeClassConfiguration)) {
            $hash = md5($typeClassConfiguration);
            $typeClassName = $typeClassConfiguration;
        } else {
            throw new \InvalidArgumentException('The Type classname can be of type string or an array of string');
        }

        if (!is_subclass_of($typeClassName, Type::class)) {
            throw new \InvalidArgumentException(sprintf('The TypeResolver can only resolve types extending "GraphQL\Type\Definition\Type", got "%s"', $typeClassConfiguration), 1461436398);
        }
        if (!isset($this->types[$hash])) {
            // forward recursive requests of the same type to a closure to prevent endless loops
            $this->types[$hash] = function() use ($originalTypeClassConfiguration, $additionalArguments) { return $this->get($originalTypeClassConfiguration, ...$additionalArguments); };

            $this->types[$hash] = new $typeClassName($this, ...$additionalArguments);
        }
        return $this->types[$hash];
    }
}
