<?php

namespace Madmages\Xsd\XsdToPhp;

use GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementSingle;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;
use Madmages\Xsd\XsdToPhp\Naming\NamingStrategy;
use Madmages\Xsd\XsdToPhp\Php\Types;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class AbstractConverter
{
    use LoggerAwareTrait;

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

    /** @var \Madmages\Xsd\XsdToPhp\Naming\NamingStrategy */
    private $namingStrategy;

    public function __construct(NamingStrategy $namingStrategy, LoggerInterface $logger = null)
    {
        $this->namingStrategy = $namingStrategy;
        $this->logger = $logger ?: new NullLogger();

        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'gYearMonth', function () {
            return Types::INT;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'gMonthDay', function () {
            return Types::INT;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'gMonth', function () {
            return Types::INT;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'gYear', function () {
            return Types::INT;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'NMTOKEN', function () {
            return Types::STRING;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'NMTOKENS', function () {
            return Types::STRING;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'QName', function () {
            return Types::STRING;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'NCName', function () {
            return Types::STRING;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'decimal', function () {
            return Types::FLOAT;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'float', function () {
            return Types::FLOAT;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'double', function () {
            return Types::FLOAT;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'string', function () {
            return Types::STRING;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'normalizedString', function () {
            return Types::STRING;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'integer', function () {
            return Types::INT;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'int', function () {
            return Types::INT;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'unsignedInt', function () {
            return Types::INT;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'negativeInteger', function () {
            return Types::INT;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'positiveInteger', function () {
            return Types::INT;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'nonNegativeInteger', function () {
            return Types::INT;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'nonPositiveInteger', function () {
            return Types::INT;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'long', function () {
            return Types::INT;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'unsignedLong', function () {
            return Types::INT;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'short', function () {
            return Types::INT;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'boolean', function () {
            return Types::BOOL;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'nonNegativeInteger', function () {
            return Types::INT;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'positiveInteger', function () {
            return Types::INT;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'language', function () {
            return Types::STRING;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'token', function () {
            return Types::STRING;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'anyURI', function () {
            return Types::STRING;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'byte', function () {
            return Types::STRING;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'duration', function () {
            return \DateInterval::class;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'ID', function () {
            return Types::STRING;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'IDREF', function () {
            return Types::STRING;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'IDREFS', function () {
            return Types::STRING;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'Name', function () {
            return Types::STRING;
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'NCName', function () {
            return Types::STRING;
        });
    }

    abstract public function convert(array $schemas);

    public function addAliasMap($ns, $name, callable $handler): void
    {
        $this->logger->info("Added map $ns $name");
        $this->typeAliases[$ns][$name] = $handler;
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
    public function getTypeAlias($type, Schema $schema = null)
    {
        $schema = $schema ?? $type->getSchema();

        $cid = $schema->getTargetNamespace() . '|' . $type->getName();
        if (isset($this->aliasCache[$cid])) {
            return $this->aliasCache[$cid];
        }
        if (isset($this->typeAliases[$schema->getTargetNamespace()][$type->getName()])) {
            return $this->aliasCache[$cid] = call_user_func($this->typeAliases[$schema->getTargetNamespace()][$type->getName()], $type);
        }
    }

    public function addNamespace($ns, $phpNamespace)
    {
        $this->logger->info("Added ns mapping {$ns}, {$phpNamespace}");
        $this->namespaces[$ns] = $phpNamespace;
        return $this;
    }

    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    /**
     * @return \Madmages\Xsd\XsdToPhp\Naming\NamingStrategy
     */
    protected function getNamingStrategy(): NamingStrategy
    {
        return $this->namingStrategy;
    }

    protected function cleanName($name)
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
