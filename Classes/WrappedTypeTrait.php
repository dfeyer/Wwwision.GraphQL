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
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Utils\TypeInfo;
use Neos\Flow\Annotations as Flow;

trait WrappedTypeTrait
{
    /**
     * @var array
     */
    private static $initializedTypes = [];

    /**
     * @var Closure
     */
    protected $typeWrapper;

    /**
     * @var TypeResolver
     */
    protected $typeResolver;

    /**
     * @var mixed
     */
    private $initialConstructorArguments;

    public function __construct()
    {
        $this->initialConstructorArguments = func_get_args();
    }

    /**
     * @return ObjectType|InterfaceType|UnionType|ScalarType|InputObjectType|EnumType
     */
    public function initializeType(): Type
    {
        if (isset(self::$initializedTypes[__CLASS__])) {
            $type = self::$initializedTypes[__CLASS__];
        } else {
            parent::construct(...$this->initialConstructorArguments);
            $type = self::$initializedTypes[__CLASS__] = $this;
        }
        return $this->typeWrapper ? ($this->typeWrapper)($type) : $type;
    }
}
