<?php

namespace Madmages\Xsd\XsdToPhp\Handler;

use Doctrine\Common\Inflector\Inflector;
use Madmages\Xsd\XsdToPhp\ChunkClass;
use Madmages\Xsd\XsdToPhp\ChunkClassProperty;
use Madmages\Xsd\XsdToPhp\ChunkProperty;
use Madmages\Xsd\XsdToPhp\ChunkValidator;
use Madmages\Xsd\XsdToPhp\Config;
use Madmages\Xsd\XsdToPhp\Contract\ClassProperty;
use Madmages\Xsd\XsdToPhp\Types;
use Zend\Code\Generator\ClassGenerator as ZendClass;
use Zend\Code\Generator\DocBlock\Tag\ParamTag;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;

class Php
{
    public const ELEMENT_CONTENT_PROPERTY = '__value';

    /** @var Config */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param ChunkClass $chunk_class
     * @return null|ZendClass
     * @throws \RuntimeException
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     */
    public function handle(ChunkClass $chunk_class): ?ZendClass
    {
        $zend_class = new ZendClass();
        $doc_block = $this->createDocBlock(
            "Class representing {$chunk_class->getName()}",
            null,
            null,
            $chunk_class->getComment()
        );

        $zend_class
            ->setNamespaceName($chunk_class->getPhpNamespace())
            ->setName($chunk_class->getName())
            ->setDocBlock($doc_block);

        if ($extends = $chunk_class->getParent()) {
            $zend_class->setExtendedClass($extends->getFullClassName());
        }

        if ($this->handleClassBody($zend_class, $chunk_class)) {
            return $this->config->emitHandler(Config::HANDLERS_CLASS, $zend_class);
        }

        return null;
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
     * @param ChunkClassProperty|ChunkProperty $property
     * @return PropertyGenerator
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     */
    private function handleProperty($property): PropertyGenerator
    {
        // VALIDATORS
        $validators = $property->getValidators();
        $validator_names = [];
        if (!empty($validators)) {
            /** @var ChunkValidator $validator */
            foreach ($validators as $validator) {
                foreach ($validator->flatten() as $validator_) {
                    $validator_names[] = $validator_->getName();
                }
            }
        }
        $validator_string = 'VALIDATORS_' . count($validator_names) . "\n" . implode("\n", $validator_names) . "\n\n";

        $property_name = $property instanceof ChunkClassProperty ? self::ELEMENT_CONTENT_PROPERTY : $property->getName();
        $property_type = $property->getType()->getTypeName();
        $nullable = $property->isNullable() ? Types::F_NULL : 0;
        $multiple = $property->isMultiple() ? Types::F_ARRAY : 0;
        $f_type = $nullable + $multiple;

        $doc_block = $this->createDocBlock(
            $validator_string,
            null,
            [$property_name => Types::format($property_type, $f_type + Types::F_DOC)],
            $property->getComment()
        );

        /** @var PropertyGenerator $property_generator */
        $property_generator = (new PropertyGenerator())
            ->setDefaultValue($property->getDefaultValue())
            ->setName($property_name)
            ->setDocBlock($doc_block)
            ->setVisibility(PropertyGenerator::VISIBILITY_PRIVATE);

        return $property_generator;
    }

    /**
     * @param ZendClass $zend_class
     * @param ChunkClass $class
     * @return bool
     * @throws \RuntimeException
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     */
    private function handleClassBody(ZendClass $zend_class, ChunkClass $class): bool
    {
        $properties = $class->getProperties();
        if ($class instanceof ChunkClassProperty) {
            $properties[] = $class;
        }

        // Generate properties
        foreach ($properties as $property) {
            $zend_class->addPropertyFromGenerator($this->handleProperty($property));
        }

        // Generate methods
        foreach ($properties as $property) {
            $this->handleMethods($zend_class, $property, $class);
        }

        return true;
    }

    /**
     * @param ZendClass $zend_class
     * @param ChunkProperty|ChunkClassProperty $property
     * @param ChunkClass $class
     * @throws \RuntimeException
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     */
    private function handleMethods(ZendClass $zend_class, $property, ChunkClass $class): void
    {
        $methods = [];
        if ($property->isMultiple()) {
            $methods = $this->handleAddToArrayMethod($property, $class);
        }

        $methods = array_merge(
            $methods,
            $this->handleGetterMethod($property),
            $this->handleSetterMethod($property, $class)
        );

        foreach ($methods as $method) {
            $method = $this->config->emitHandler(Config::HANDLERS_METHOD, $method);
            $zend_class->addMethodFromGenerator($method);
        }
    }

    /**
     * @param ClassProperty|ChunkProperty|ChunkClassProperty $property
     * @param ChunkClass $class
     * @return MethodGenerator[]
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     */
    private function handleAddToArrayMethod(ClassProperty $property, ChunkClass $class): array
    {
        $property_type = $property->getType()->getTypeName();
        $nullable = $property->isNullable() ? Types::F_NULL : 0;
        $multiple = $property->isMultiple() ? Types::F_ARRAY : 0;
        $f_type = $nullable + $multiple;

        $property_name = $property instanceof ChunkClassProperty ? self::ELEMENT_CONTENT_PROPERTY : $property->getName();

        $doc_block = $this->createDocBlock(
            'Adds as ' . $property_name,
            Types::STATIC,
            [$property_name => Types::format($property_type, $f_type + Types::F_DOC)],
            $property->getComment()
        );

        $method_body = '$this->' . $property_name . '[] = $' . $property_name . ';' . PHP_EOL . 'return $this;';

        /** @var MethodGenerator $method */
        $method = (new MethodGenerator())
            ->setParameter(new ParameterGenerator($property_name, $this->config->getTypes() ? Types::format($property_type, $f_type) : null))
            ->setReturnType($this->config->getTypes() ? $class->getFullClassName() : null)
            ->setBody($method_body)
            ->setName('addTo' . Inflector::classify($property_name))
            ->setDocBlock($doc_block);

        return [$method];
    }

    /**
     * @param ClassProperty|ChunkClassProperty|ChunkProperty $property
     * @return MethodGenerator[]
     * @throws \RuntimeException
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     */
    private function handleGetterMethod(ClassProperty $property): array
    {
        $methods = [];

        $property_name = $property instanceof ChunkClassProperty ? self::ELEMENT_CONTENT_PROPERTY : $property->getName();
        $property_type = $property->getType()->getTypeName();
        $nullable = $property->isNullable() ? Types::F_NULL : 0;
        $multiple = $property->isMultiple() ? Types::F_ARRAY : 0;
        $f_type = $nullable + $multiple;

        // For multiple values some additional methods will be present: isset($index),unset($index)
        if ($property->isMultiple()) {
            $methods[] = $this->getIssetUnsetMethodForArray(true, $property_name, $property->getComment());
            $methods[] = $this->getIssetUnsetMethodForArray(false, $property_name, $property->getComment());
        }

        $doc_block = $this->createDocBlock(
            "Gets as {$property_name}",
            Types::format($property_type, $f_type + Types::F_DOC),
            null,
            $property->getComment()
        );

        /** @var MethodGenerator $method */
        $method = (new MethodGenerator())
            ->setBody('return $this->' . $property_name . ';')
            ->setReturnType($this->config->getTypes() ? Types::format($property_type, $f_type) : null)
            ->setName('get' . Inflector::classify($property_name))
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
            ->setParameter(new ParameterGenerator('index', $this->config->getTypes() ? Types::INT : null))
            ->setReturnType($this->config->getTypes() ? $return_type : null)
            ->setName($prefix . 'set' . Inflector::classify($property_name))
            ->setDocBlock($doc_block);

        return $method;
    }

    /**
     * @param ClassProperty|ChunkProperty|ChunkClassProperty $property
     * @param ChunkClass $class
     * @return MethodGenerator[]
     * @throws \RuntimeException
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     */
    private function handleSetterMethod(ClassProperty $property, ChunkClass $class): array
    {
        $property_name = $property instanceof ChunkClassProperty ? self::ELEMENT_CONTENT_PROPERTY : $property->getName();
        $property_type = $property->getType()->getTypeName();
        $nullable = $property->isNullable() ? Types::F_NULL : 0;
        $multiple = $property->isMultiple() ? Types::F_ARRAY : 0;
        $f_type = $nullable + $multiple;

        $doc_block = $this->createDocBlock(
            'Sets a new ' . $property_name,
            Types::STATIC,
            [$property_name => Types::format($property_type, $f_type + Types::F_DOC)],
            $property->getComment()
        );

        $method_body = '$this->' . $property_name . ' = $' . $property_name . ';' . PHP_EOL;
        $method_body .= 'return $this;';

        $parameter_generator = new ParameterGenerator(
            $property_name,
            $this->config->getTypes() ? Types::format($property_type, $f_type) : null
        );

        if ($default = $property->getDefaultValue()) {
            $parameter_generator->setDefaultValue($default);
        }

        /** @var MethodGenerator $method */
        $method = (new MethodGenerator())
            ->setBody($method_body)
            ->setReturnType($this->config->getTypes() ? $class->getFullClassName() : null)
            ->setParameter($parameter_generator)
            ->setName('set' . Inflector::classify($property_name))
            ->setDocBlock($doc_block);

        return [$method];
    }
}
