<?php

namespace Tests\Unit;

use ReflectionProperty;
use Tests\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

abstract class AbstractTestCase extends TestCase
{

    /**
     * @throws ReflectionException
     */
    protected function getObjectMethod( string $className, string $methodName ): ReflectionMethod
    {
        $class = new ReflectionClass( $className );
        $method = $class->getMethod( $methodName );
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @throws ReflectionException
     */
    protected function setObjectProperty(object $object, string $propertyName, mixed $value ): ReflectionProperty
    {
        $property = new ReflectionProperty( get_class($object), $propertyName );
        $property->setAccessible(true);
        $property->setValue( $object, $value );
        $property->setAccessible(false);

        return $property;
    }

    /**
     * @throws ReflectionException
     */
    protected function getObjectProperty(object $object, string $propertyName): mixed
    {
        $reflectionClass = new ReflectionClass( get_class($object) );
        $reflectionProperty = $reflectionClass->getProperty($propertyName);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($object);
    }

}
