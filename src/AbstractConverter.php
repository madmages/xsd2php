<?php

namespace Madmages\Xsd\XsdToPhp;

use GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute;
use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementSingle;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;

abstract class AbstractConverter
{
    protected $base_schemas = [
        XMLSchema::NAMESPACE,
        XMLSchema::NAMESPACE_OLD
    ];

    protected $namespaces = [
        XMLSchema::NAMESPACE     => '',
        XMLSchema::NAMESPACE_OLD => ''
    ];
    protected $type_aliases = [];
    protected $alias_cache = [];

    /** @var \Madmages\Xsd\XsdToPhp\NamingStrategy */
    private $naming_strategy;

    public function __construct(NamingStrategy $naming_strategy, Config $config)
    {
        $this->namespaces = array_replace($this->namespaces, $config->getNamespaces());
        $this->naming_strategy = $naming_strategy;

        /** @var string[][] $default_types */
        $default_types = array_replace(XSD2PHPTypes::TYPES, $config->getAliases());

        foreach ($default_types as $namespace => $types) {
            foreach ($types as $xsd_type => $php_type) {
                $this->addAliasMap($namespace, $xsd_type, function () use ($php_type) {
                    return $php_type;
                });
            }
        }
    }

    public function addAliasMap(string $xsd_namespace, string $xsd_type, callable $handler): void
    {
        $this->type_aliases[$xsd_namespace][$xsd_type] = $handler;
    }

    /**
     * @param Schema[] $schemas
     * @return mixed
     */
    abstract public function convert(array $schemas);

    /**
     * @param Attribute|Type $type
     * @param Schema|null $schema
     * @return mixed
     */
    public function getTypeAlias($type, Schema $schema = null): ?string
    {
        $schema = $schema ?? $type->getSchema();

        $alias_cache_key = $schema->getTargetNamespace() . '|' . $type->getName();
        if (array_key_exists($alias_cache_key, $this->alias_cache)) {
            return $this->alias_cache[$alias_cache_key];
        }

        if (isset($this->type_aliases[$schema->getTargetNamespace()][$type->getName()])) {
            $this->alias_cache[$alias_cache_key] = call_user_func($this->type_aliases[$schema->getTargetNamespace()][$type->getName()], $type);
        } else {
            $this->alias_cache[$alias_cache_key] = null;
        }

        return $this->alias_cache[$alias_cache_key];
    }

    protected function getNamingStrategy(): NamingStrategy
    {
        return $this->naming_strategy;
    }

    /**
     * @param Type $type
     * @return Type|null
     */
    protected function isArrayType(Type $type): ?Type
    {
        if ($type instanceof SimpleType) {
            return $type->getList();
        }

        return null;
    }

    protected function isArrayNestedElement(Type $type): ?Element
    {
        if (
            $type instanceof ComplexType
            && !$type->getParent()
            && !$type->getAttributes()
            && count($elements = $type->getElements()) === 1
        ) {
            return $this->isArrayElement(reset($elements));
        }

        return null;
    }

    /**
     * @param mixed $element
     * @return ElementSingle|null
     */
    protected function isArrayElement($element): ?Element
    {
        if ($element instanceof ElementSingle && ($element->getMax() > 1 || $element->getMax() === -1)) {
            return $element;
        }

        return null;
    }
}
