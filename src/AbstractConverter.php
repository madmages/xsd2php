<?php

namespace Madmages\Xsd\XsdToPhp;

use GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementSingle;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;

abstract class AbstractConverter
{
    protected $baseSchemas = [
        'http://www.w3.org/2001/XMLSchema',
        'http://www.w3.org/XML/1998/namespace'
    ];

    protected $namespaces = [
        'http://www.w3.org/2001/XMLSchema'     => '',
        'http://www.w3.org/XML/1998/namespace' => ''
    ];
    protected $typeAliases = [];
    protected $aliasCache = [];

    /** @var \Madmages\Xsd\XsdToPhp\NamingStrategy */
    private $namingStrategy;

    public function __construct(NamingStrategy $naming_strategy)
    {
        $this->namespaces = array_replace($this->namespaces, Config::getNamespaces());
        $this->namingStrategy = $naming_strategy;

        foreach (XSD2PHPTypes::TYPES as $namespace => $types) {
            foreach ($types as $xsd_type => $php_type) {
                $this->addAliasMap($namespace, $xsd_type, function () use ($php_type) {
                    return $php_type;
                });
            }
        }
    }

    /**
     * @param Schema[] $schemas
     * @return mixed
     */
    abstract public function convert(array $schemas);

    public function addAliasMap(string $namespace, string $xsd_type, callable $handler): void
    {
        $this->typeAliases[$namespace][$xsd_type] = $handler;
    }

    public function addAliasMapType($ns, $name, $type): void
    {
        $this->addAliasMap($ns, $name, function () use ($type) {
            return $type;
        });
    }

    /**
     * @param Type|Attribute $type
     * @param Schema|null $schema
     * @return mixed
     */
    public function getTypeAlias($type, Schema $schema = null): ?string
    {
        $schema = $schema ?? $type->getSchema();

        $alias_cache_key = $schema->getTargetNamespace() . '|' . $type->getName();
        if (array_key_exists($alias_cache_key, $this->aliasCache)) {
            return $this->aliasCache[$alias_cache_key];
        }
        if (isset($this->typeAliases[$schema->getTargetNamespace()][$type->getName()])) {
            $this->aliasCache[$alias_cache_key] = call_user_func($this->typeAliases[$schema->getTargetNamespace()][$type->getName()], $type);
        } else {
            $this->aliasCache[$alias_cache_key] = null;
        }

        return $this->aliasCache[$alias_cache_key];
    }

    public function addNamespace(string $xsd_namespace, string $php_namespace): self
    {
        $this->namespaces[$xsd_namespace] = $php_namespace;

        return $this;
    }

    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    protected function getNamingStrategy(): NamingStrategy
    {
        return $this->namingStrategy;
    }

    protected function cleanName(string $name): string
    {
        return preg_replace('/<.*>/', '', $name);
    }

    /**
     * @param Type $type
     * @return \GoetasWebservices\XML\XSDReader\Schema\Type\Type|null
     */
    protected function isArrayType(Type $type): ?Type
    {
        if ($type instanceof SimpleType) {
            return $type->getList();
        }

        return null;
    }

    /**
     * @param Type $type
     * @return \GoetasWebservices\XML\XSDReader\Schema\Element\ElementSingle|null
     */
    protected function isArrayNestedElement(Type $type): ?ElementSingle
    {
        if ($type instanceof ComplexType && !$type->getParent() && !$type->getAttributes() && count($type->getElements()) === 1) {
            $elements = $type->getElements();
            return $this->isArrayElement(reset($elements));
        }

        return null;
    }

    /**
     * @param mixed $element
     * @return \GoetasWebservices\XML\XSDReader\Schema\Element\ElementSingle|null
     */
    protected function isArrayElement($element): ?ElementSingle
    {
        if ($element instanceof ElementSingle && ($element->getMax() > 1 || $element->getMax() === -1)) {
            return $element;
        }

        return null;
    }
}
