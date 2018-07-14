<?php

namespace Madmages\Xsd\XsdToPhp\Php;

use Doctrine\Common\Inflector\Inflector;
use Madmages\Xsd\XsdToPhp\ChunkClass;
use Madmages\Xsd\XsdToPhp\ChunkClassProperty;
use Madmages\Xsd\XsdToPhp\ChunkProperty;
use Madmages\Xsd\XsdToPhp\ClassProperty;
use Madmages\Xsd\XsdToPhp\Config;
use Madmages\Xsd\XsdToPhp\Exception\NullPointer;
use Madmages\Xsd\XsdToPhp\Php\Checker\AbstractChecker;
use Madmages\Xsd\XsdToPhp\Php\Structure\PHPClass;
use Madmages\Xsd\XsdToPhp\Php\Structure\PHPClassOf;
use Madmages\Xsd\XsdToPhp\Php\Structure\PHPProperty;
use Zend\Code\Generator\ClassGenerator as ZendClass;
use Zend\Code\Generator\DocBlock\Tag\ParamTag;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;

class ClassGenerator
{
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
     * @throws NullPointer
     */
    public function generateClass(ChunkClass $chunk_class): ?ZendClass
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
        $type = $property->getType();

        $doc_block = $this->createDocBlock(
            null,
            null,
            [$property->getName() => $type->getTypeName()],
            $property->getComment()
        );

        /** @var PropertyGenerator $property_generator */
        $property_generator = (new PropertyGenerator())
            ->setDefaultValue($property->getDefaultValue())
            ->setName($property->getName())
            ->setDocBlock($doc_block)
            ->setVisibility(PropertyGenerator::VISIBILITY_PRIVATE);

        return $property_generator;
    }

    /**
     * @param ZendClass $zend_class
     * @param PHPProperty $property
     * @param PHPClass $parent_class
     * @throws \RuntimeException
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     * @throws NullPointer
     */
    private function handleValueMethod(ZendClass $zend_class, PHPProperty $property, PHPClass $parent_class): void
    {
        $enable_types = $this->config->getTypes();

        $type = $property->getType();
        if ($type === null) {
            throw new NullPointer();
        }

        //---------------------------------------------------------- CONSTRUCTOR
        $doc_block = $this->createDocBlock(
            'Construct',
            null,
            ['value' => $type->getPhpType() . '|' . Types::NULL]
        );

        $parameter = ['name' => 'value', 'defaultValue' => null];
        if ($enable_types && ($parameter_type = $type->getPhpType(false))) {
            $parameter['type'] = $parameter_type;
        }

        /** @var MethodGenerator $constructor */
        $constructor = (new MethodGenerator())
            ->setBody('$this->setValue($value);')
            ->setParameter($parameter)
            ->setName('__construct')
            ->setDocBlock($doc_block);


        //---------------------------------------------------------- SET VALUE METHOD
        $doc_block = $this->createDocBlock(
            'Sets value',
            Types::STATIC,
            ['value' => $type->getPhpType()]
        );

        $value_method_body = '$value !== null && $this->validate($value);' . PHP_EOL;
        $value_method_body .= '$this->__value = $value;' . PHP_EOL . PHP_EOL;
        $value_method_body .= 'return $this;' . PHP_EOL;

        $parameter = ['name' => 'value'];
        if ($enable_types && ($parameter_type = $type->getPhpType(false, true))) {
            $parameter['type'] = $parameter_type;
        }

        /** @var MethodGenerator $set_value_method */
        $set_value_method = (new MethodGenerator())
            ->setParameter($parameter)
            ->setBody($value_method_body)
            ->setDocBlock($doc_block)
            ->setName('setValue');

        //---------------------------------------------------------- GET VALUE METHOD
        $doc_block = $this->createDocBlock(
            'Get value',
            ($type instanceof PHPClassOf ? $type->getArg()->getType()->getPhpType() . '[]' : $type->getPhpType()) . '|' . Types::NULL
        );

        $value_method_body = 'return $this->__value;' . PHP_EOL;

        /** @var MethodGenerator $get_value_method */
        $get_value_method = (new MethodGenerator())
            ->setBody($value_method_body)
            ->setReturnType($enable_types ? ($type instanceof PHPClassOf ? '?' . Types::ARRAY : $type->getPhpType(false, true)) : null)
            ->setDocBlock($doc_block)
            ->setName('getValue');

        //---------------------------------------------------------- __toString
        $doc_block = $this->createDocBlock(
            'Gets a string value',
            Types::STRING
        );

        /** @var MethodGenerator $to_string_method */
        $to_string_method = (new MethodGenerator())
            ->setBody('return (' . Types::STRING . ')$this->' . $property->getName() . ';')
            ->setReturnType($enable_types ? Types::STRING : null)
            ->setDocBlock($doc_block)
            ->setName('__toString');

        //---------------------------------------------------------- VALIDATORS
        $code = '';
        if ($this->config->getValidators()) {
            $code = $this->getCheckCode('$value', PHPClass::VALUE_CLASS, $parent_class);
        }

        $doc_block = $this->createDocBlock(
            'Validators',
            Types::VOID,
            ['value' => Types::MIXED]
        );

        /** @var MethodGenerator $validators_method */
        $validators_method = (new MethodGenerator())
            ->setBody($code)
            ->setParameter('value')
            ->setReturnType($enable_types ? Types::VOID : null)
            ->setName('validate')
            ->setDocBlock($doc_block);

        //---------------------------------- SET METHODS
        $zend_class
            ->addMethodFromGenerator($constructor)
            ->addMethodFromGenerator($set_value_method)
            ->addMethodFromGenerator($get_value_method)
            ->addMethodFromGenerator($validators_method)
            ->addMethodFromGenerator($to_string_method);
    }

    /**
     * @param ZendClass $zend_class
     * @param ChunkClass $class
     * @return bool
     * @throws \RuntimeException
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     * @throws NullPointer
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
     * @throws NullPointer
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

        $arg_name = $property->getName();

        $doc_block = $this->createDocBlock(
            'Adds as ' . $arg_name,
            Types::STATIC,
            [$arg_name => Types::format($property_type, $f_type + Types::F_DOC)],
            $property->getComment()
        );

        $method_body = '$this->' . $property->getName() . '[] = $' . $arg_name . ';' . PHP_EOL . 'return $this;';

        /** @var MethodGenerator $method */
        $method = (new MethodGenerator())
            ->setParameter(new ParameterGenerator($arg_name, $this->config->getTypes() ? Types::format($property_type, $f_type) : null))
            ->setReturnType($this->config->getTypes() ? $class->getFullClassName() : null)
            ->setBody($method_body)
            ->setName('addTo' . Inflector::classify($property->getName()))
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

        $property_type = $property->getType()->getTypeName();
        $nullable = $property->isNullable() ? Types::F_NULL : 0;
        $multiple = $property->isMultiple() ? Types::F_ARRAY : 0;
        $f_type = $nullable + $multiple;

        // For multiple values some additional methods will be present: isset($index),unset($index)
        if ($property->isMultiple()) {
            $methods[] = $this->getIssetUnsetMethodForArray(true, $property->getName(), $property->getComment());
            $methods[] = $this->getIssetUnsetMethodForArray(false, $property->getName(), $property->getComment());
        }

        $doc_block = $this->createDocBlock(
            "Gets as {$property->getName()}",
            Types::format($property_type, $f_type + Types::F_DOC),
            null,
            $property->getComment()
        );

        /** @var MethodGenerator $method */
        $method = (new MethodGenerator())
            ->setBody('return $this->' . $property->getName() . ';')
            ->setReturnType($this->config->getTypes() ? Types::format($property_type, $f_type) : null)
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
        $property_type = $property->getType()->getTypeName();
        $nullable = $property->isNullable() ? Types::F_NULL : 0;
        $multiple = $property->isMultiple() ? Types::F_ARRAY : 0;
        $f_type = $nullable + $multiple;

        $doc_block = $this->createDocBlock(
            'Sets a new ' . $property->getName(),
            Types::STATIC,
            [$property->getName() => Types::format($property_type, $f_type + Types::F_DOC)],
            $property->getComment()
        );

        $method_body = '$this->' . $property->getName() . ' = $' . $property->getName() . ';' . PHP_EOL;
        $method_body .= 'return $this;';

        $parameter_generator = new ParameterGenerator(
            $property->getName(),
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
            ->setName('set' . Inflector::classify($property->getName()))
            ->setDocBlock($doc_block);

        return [$method];
    }

    /**
     * @param string $check_var
     * @param string $property
     * @param PHPClass $parent_class
     * @return string
     * @throws \RuntimeException
     */
    private function getCheckCode(string $check_var, string $property, PHPClass $parent_class): string
    {
        $checkers = $this->flatCheckers($parent_class, $property);

        $code = [];
        foreach ($checkers as $checker_type => $args) {
            $code[] = AbstractChecker::make($check_var, $checker_type, $args)->render();
        }

        return implode("\n\n", $code);
    }

    private function flatCheckers(?PHPClass $class, string $property): array
    {
        if ($class === null) {
            return [];
        }

        $result = [];
        foreach ($class->getChecks($property) as $key => $value) {
            $result[$key] = $value;
        }

        return array_replace(
            $result,
            $this->flatCheckers($class->getExtends() ?: null, $property)
        );
    }
}
