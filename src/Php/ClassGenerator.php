<?php

namespace Madmages\Xsd\XsdToPhp\Php;

use Doctrine\Common\Inflector\Inflector;
use Madmages\Xsd\XsdToPhp\Php\Structure\PHPClass;
use Madmages\Xsd\XsdToPhp\Php\Structure\PHPClassOf;
use Madmages\Xsd\XsdToPhp\Php\Structure\PHPProperty;
use Zend\Code\Generator\ClassGenerator as ZendClass;
use Zend\Code\Generator\DocBlock\Tag\ParamTag;
use Zend\Code\Generator\DocBlock\Tag\PropertyTag;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;

class ClassGenerator
{

    /**
     * @param PHPClass $type
     * @return null|ZendClass
     * @throws \RuntimeException
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     */
    public function generateClass(PHPClass $type): ?ZendClass
    {
        $zend_class = new ZendClass();
        $doc_block = $this->createDocBlock(
            "Class representing {$type->getName()}",
            null,
            null,
            $type->getDoc()
        );

        $zend_class
            ->setNamespaceName($type->getNamespace())
            ->setName($type->getName())
            ->setDocBlock($doc_block);

        if ($extends = $type->getExtends()) {
            if ($simple_type = $extends->getSimpleType()) {
                $this->handleProperty($zend_class, $simple_type);
                $this->handleValueMethod($zend_class, $simple_type);
            } else {
                $zend_class->setExtendedClass($extends_full_name = $extends->getFullName());

                if ($extends->getNamespace() !== $type->getNamespace()) {
                    if ($extends->getName() === $type->getName()) {
                        $zend_class->addUse($extends_full_name, $extends->getName() . 'Base');
                    } else {
                        $zend_class->addUse($extends_full_name);
                    }
                }
            }
        }

        if ($this->handleClassBody($zend_class, $type)) {
            return $zend_class;
        }

        return null;
    }

    /**
     * @param ZendClass $zend_class
     * @param PHPProperty $property
     * @throws \RuntimeException
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     */
    private function handleProperty(ZendClass $zend_class, PHPProperty $property): void
    {
        $property_generator = new PropertyGenerator($property->getName());
        $property_generator->setVisibility(PropertyGenerator::VISIBILITY_PRIVATE);

        $docBlock = new DocBlockGenerator();
        $docBlock->setWordWrap(false);
        if ($property->getDoc()) {
            $docBlock->setLongDescription($property->getDoc());
        }

        if ($type = $property->getType()) {
            $tag = new PropertyTag($property->getName(), Types::MIXED);

            if ($type instanceof PHPClassOf) {
                $arg_type = $type->getArg()->getType();
                if ($arg_type === null) {
                    throw new \RuntimeException('Null occur');
                }

                $tag->setTypes(Types::typedArray($arg_type->getPhpType()));
                if (
                    ($simple_type = $arg_type->getSimpleType())
                    && ($sp_type = $simple_type->getType())
                ) {
                    $tag->setTypes(Types::typedArray($sp_type->getPhpType()));
                }

                $property_generator->setDefaultValue($type->getArg()->getDefault());
            } else {
                if ($type->isNativeType()) {
                    $tag->setTypes($type->getPhpType());
                } elseif (($simple_type = $type->getSimpleType()) && ($sp_type = $simple_type->getType())) {
                    $tag->setTypes($sp_type->getPhpType());
                } else {
                    $tag->setTypes($property->getType()->getPhpType());
                }
            }

            $docBlock->setTag($tag);
        }

        $property_generator->setDocBlock($docBlock);
        $zend_class->addPropertyFromGenerator($property_generator);
    }

    /**
     * @param ZendClass $generator
     * @param PHPProperty $property
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     */
    private function handleValueMethod(ZendClass $generator, PHPProperty $property): void
    {
        $type = $property->getType();

        $docblock = new DocBlockGenerator('Construct');
        $docblock->setWordWrap(false);
        $paramTag = new ParamTag('value');
        $paramTag->setTypes(($type ? $type->getPhpType() : Types::MIXED));

        $docblock->setTag($paramTag);

        //todo fix constructor
        $param = new ParameterGenerator('value');
        if ($type && !$type->isNativeType()) {
            $param->setType($type->getPhpType());
        }
        $method = new MethodGenerator('__construct', [
            $param
        ]);
        $method->setDocBlock($docblock);
        $method->setBody('$this->value($value);');

        $generator->addMethodFromGenerator($method);

        $docblock = new DocBlockGenerator('Gets or sets the inner value');
        $docblock->setWordWrap(false);

        $returnTag = new ReturnTag(Types::MIXED);

        if ($type && $type instanceof PHPClassOf) {
            $returnTag->setTypes($type->getArg()->getType()->getPhpType() . '[]');
        } elseif ($type) {
            $returnTag->setTypes($type->getPhpType());
        }
        $docblock->setTag($returnTag);

        $param = new ParameterGenerator('value');
        $param->setDefaultValue(null);

        if ($type && !$type->isNativeType()) {
            $param->setType($type->getPhpType());
        }
        $method = new MethodGenerator('value', []);
        $method->setDocBlock($docblock);

        $methodBody = 'if ($args = func_get_args()) {' . PHP_EOL;
        $methodBody .= '    $this->' . $property->getName() . ' = $args[0];' . PHP_EOL;
        $methodBody .= '}' . PHP_EOL;
        $methodBody .= 'return $this->' . $property->getName() . ';' . PHP_EOL;
        $method->setBody($methodBody);

        $generator->addMethodFromGenerator($method);

        $docblock = new DocBlockGenerator('Gets a string value');
        $docblock->setWordWrap(false);
        $docblock->setTag(new ReturnTag(Types::STRING));
        $method = new MethodGenerator('__toString');
        $method->setDocBlock($docblock);
        $method->setBody('return (' . Types::STRING . ')$this->' . $property->getName() . ';');
        $generator->addMethodFromGenerator($method);
    }

    /**
     * @param ZendClass $zend_class
     * @param PHPClass $class
     * @return bool
     * @throws \RuntimeException
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     */
    private function handleClassBody(ZendClass $zend_class, PHPClass $class): bool
    {
        // Set properties
        foreach ($class->getProperties() as $property) {
            if ($property->getName() !== '__value') {
                $this->handleProperty($zend_class, $property);
            }
        }

        // Set methods
        foreach ($class->getProperties() as $property) {
            if ($property->getName() !== '__value') {
                $this->handleMethod($zend_class, $property, $class);
            }
        }

        return !($class->hasProperty('__value') && count($class->getProperties()) === 1);
    }

    /**
     * @param ZendClass $zend_class
     * @param PHPProperty $property
     * @param PHPClass $class
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     * @throws \RuntimeException
     */
    private function handleMethod(ZendClass $zend_class, PHPProperty $property, PHPClass $class): void
    {
        $methods = [];
        if ($property->getType() instanceof PHPClassOf) {
            $methods = $this->handleAddToArrayMethod($property, $class);
        }

        $methods = array_merge(
            $methods,
            $this->handleGetterMethod($property),
            $this->handleSetterMethod($property, $class)
        );

        foreach ($methods as $method) {
            $zend_class->addMethodFromGenerator($method);
        }
    }

    /**
     * @param PHPProperty $property
     * @param PHPClass $class
     * @return MethodGenerator[]
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     * @throws \RuntimeException
     */
    private function handleAddToArrayMethod(PHPProperty $property, PHPClass $class): array
    {
        /** @var PHPClassOf $property_type */
        $property_type = $property->getType();
        if ($property_type === null) {
            throw new \RuntimeException('Null occurs');
        }

        $property_arg = $property_type->getArg();

        $arg_name = $property_arg->getName();

        $arg_type = $property_arg->getType();
        if ($arg_type === null) {
            throw new \RuntimeException('null occur');
        }

        $doc_block = $this->createDocBlock(
            'Adds as ' . $arg_name,
            Types::STATIC,
            [$arg_name => $arg_type->getPhpType()],
            $property->getDoc()
        );

        $method_body = '$this->' . $property->getName() . '[] = $' . $arg_name . ';' . PHP_EOL . 'return $this;';

        /** @var MethodGenerator $method */
        $method = (new MethodGenerator())
            ->setParameter(new ParameterGenerator($arg_name, $arg_type->getPhpType(false)))
            ->setReturnType($class->getPhpType())
            ->setBody($method_body)
            ->setName('addTo' . Inflector::classify($property->getName()))
            ->setDocBlock($doc_block);

        return [$method];
    }

    /**
     * @param PHPProperty $property
     * @return MethodGenerator[]
     * @throws \RuntimeException
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     */
    private function handleGetterMethod(PHPProperty $property): array
    {
        $methods = [];
        $property_type = $property->getType();

        // For multiple values some additional methods will be present: isset($index),unset($index)
        if ($property_type instanceof PHPClassOf) {
            $methods[] = $this->getIssetUnsetMethodForArray(true, $property->getName(), $property->getDoc());
            $methods[] = $this->getIssetUnsetMethodForArray(false, $property->getName(), $property->getDoc());
        }

        $doc_return_type = Types::MIXED;
        $method_return_type = null;
        if ($property_type) {
            if ($property_type instanceof PHPClassOf) {
                $arg_type = $property_type->getArg()->getType();
                if ($arg_type === null) {
                    throw new \RuntimeException('null occurs');
                }

                $method_return_type = ($property->getIsNullable() ? '?' : '') . Types::ARRAY;

                if ($simple_type = $arg_type->getSimpleType()) {
                    if ($sp_type = $simple_type->getType()) {
                        $doc_return_type = $sp_type->getPhpType() . '[]';
                    }
                } else {
                    $doc_return_type = $arg_type->getPhpType() . '[]';
                }
            } else {
                if ($simple_type = $property_type->getSimpleType()) {
                    if ($sp_type = $simple_type->getType()) {
                        $doc_return_type = $sp_type->getPhpType();
                        $method_return_type = $sp_type->getPhpType(false, $property->getIsNullable());
                    } else {
                        throw new \RuntimeException('unexpected');
                    }
                } else {
                    $doc_return_type = $property_type->getPhpType();
                    $method_return_type = $property_type->getPhpType(false, $property->getIsNullable());
                }
            }
        }

        $doc_block = $this->createDocBlock(
            "Gets as {$property->getName()}",
            $doc_return_type,
            null,
            $property->getDoc()
        );

        /** @var MethodGenerator $method */
        $method = (new MethodGenerator())
            ->setBody('return $this->' . $property->getName() . ';')
            ->setReturnType($method_return_type)
            ->setName('get' . Inflector::classify($property->getName()))
            ->setDocBlock($doc_block);

        $methods[] = $method;
        return $methods;
    }

    /**
     * @param string $property_name
     * @param string $property_doc
     * @param bool $isset
     * @return MethodGenerator
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     */
    private function getIssetUnsetMethodForArray(bool $isset, string $property_name, string $property_doc = null): MethodGenerator
    {
        $prefix = $isset ? 'is' : 'un';

        $return_type = $isset ? Types::BOOL : Types::VOID;
        $doc_block = $this->createDocBlock(
            "{$prefix}set {$property_name}",
            $return_type,
            ['index' => Types::INT],
            $property_doc
        );

        /** @var MethodGenerator $method */
        $method = (new MethodGenerator())
            ->setBody(($isset ? 'return ' : '') . $prefix . 'set($this->' . $property_name . '[$index]);')
            ->setParameter(new ParameterGenerator('index', Types::INT))
            ->setReturnType($return_type)
            ->setName($prefix . 'set' . Inflector::classify($property_name))
            ->setDocBlock($doc_block);

        return $method;
    }

    /**
     * @param string $short_desc
     * @param string $return_type
     * @param array $params
     * @param string|null $description
     * @return DocBlockGenerator
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     */
    private function createDocBlock(string $short_desc = null, string $return_type = null, array $params = null, string $description = null): DocBlockGenerator
    {
        $doc_block = (new DocBlockGenerator())->setWordWrap(false);

        if ($short_desc) {
            $doc_block->setShortDescription($short_desc);
        }

        if ($description) {
            $doc_block->setLongDescription($description);
        }

        if ($params !== null) {
            foreach ($params as $variable_name => $type) {
                $doc_block->setTag(new ParamTag($variable_name, $type));
            }
        }

        if ($return_type) {
            $doc_block->setTag(new ReturnTag($return_type));
        }

        return $doc_block;
    }

    /**
     * @param PHPProperty $property
     * @param PHPClass $class
     * @return MethodGenerator[]
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     * @throws \RuntimeException
     */
    private function handleSetterMethod(PHPProperty $property, PHPClass $class): array
    {
        $doc_parameter_type = null;
        $parameter_type = null;
        if ($type = $property->getType()) {
            if ($type instanceof PHPClassOf) {
                $parameter_type = Types::ARRAY;

                $arg_type = $type->getArg()->getType();
                if ($arg_type === null) {
                    throw new \RuntimeException('null occur');
                }

                $doc_parameter_type = Types::typedArray($arg_type->getPhpType());

                if (
                    ($simple_type = $arg_type->getSimpleType())
                    && ($sp_type = $simple_type->getType())
                ) {
                    $doc_parameter_type = $sp_type->getPhpType();
                }
            } else {
                if ($type->isNativeType()) {
                    $parameter_type = $type->getPhpType(false, $property->getIsNullable());
                    $doc_parameter_type = $type->getPhpType();
                } else {
                    if ($simple_type = $type->getSimpleType()) {
                        if ($sp_type = $simple_type->getType()) {
                            $parameter_type = $sp_type->getPhpType(false, $property->getIsNullable());
                            $doc_parameter_type = $sp_type->getPhpType();
                        } else {
                            throw new \RuntimeException('unexpected error');
                        }
                    } else {
                        $doc_parameter_type = $type->getPhpType();
                        $parameter_type = $type->getPhpType(false, $property->getIsNullable());
                    }
                }
            }
        }

        $doc_block = $this->createDocBlock(
            'Sets a new ' . $property->getName(),
            Types::STATIC,
            [$property->getName() => $doc_parameter_type],
            $property->getDoc()
        );

        $methodBody = '$this->' . $property->getName() . ' = $' . $property->getName() . ';' . PHP_EOL;
        $methodBody .= 'return $this;';

        /** @var MethodGenerator $method */
        $method = (new MethodGenerator())
            ->setBody($methodBody)
            ->setReturnType($class->getPhpType())
            ->setParameter(new ParameterGenerator($property->getName(), $parameter_type))
            ->setName('set' . Inflector::classify($property->getName()))
            ->setDocBlock($doc_block);

        return [$method];
    }
}
