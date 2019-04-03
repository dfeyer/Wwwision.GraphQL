<?php

namespace Wwwision\GraphQL;

use Closure;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use Neos\Flow\Annotations as Flow;

class WrappedType
{
    /**
     * @var int
     */
    protected static $usage = 0;

    /**
     * @var Closure
     */
    protected $wrapper;

    /**
     * @var TypeResolver
     */
    protected $typeResolver;

    public static function create(string $typeClassName, TypeResolver $typeResolver, Closure $wrapper = null): Type
    {
        $wrappedType = new self($typeClassName, $typeResolver, $wrapper);

        $reflection = new \ReflectionClass($typeClassName);
        $baseClass = $reflection->getShortName() . '_Original';
        $namespace = $reflection->getNamespaceName();

        $newClassName = "{$baseClass}Wrapped";

        if (!class_exists($newClassName)) {
            //prepare a code of the wrapping class that inject trait
            $wrappedType = WrappedTypeTrait::class;
            $newClassCode = "namespace {$namespace};\nclass {$newClassName} extends {$baseClass} \n{\n\tuse \\{$wrappedType};\n}";

            //run the prepared code
            eval($newClassCode);
        }

        $type = self::changeObjectClass($wrappedType, $namespace . '\\' . $newClassName);
        $type->typeWrapper = $wrapper;
        $type->typeResolver = $typeResolver;
        return $type;
    }

    protected function __construct(string $typeClassName, TypeResolver $typeResolver, Closure $wrapper = null)
    {
        self::$usage++;

        $this->generateName($typeClassName);

        $this->wrapper = $wrapper;
        $this->typeResolver = $typeResolver;
    }

    protected function generateName(string $typeClassName): void
    {
        $this->name = str_replace('\\', '_', '_WrappedType_' . $typeClassName . '_' . str_pad((string)self::$usage, 4, 0,STR_PAD_LEFT));
    }

    protected static function changeObjectClass($instance, $className)
    {
        $data = sprintf('O:%d:"%s"%s', strlen($className), $className, strstr(strstr(serialize($instance), '"'), ':'));
        return unserialize($data);
    }
}
