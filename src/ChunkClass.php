<?php

namespace Madmages\Xsd\XsdToPhp;

use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;
use Madmages\Xsd\XsdToPhp\Contract\ClassProperty;
use Madmages\Xsd\XsdToPhp\Contract\PHPType;

class ChunkClass extends Chunk implements PHPType
{
    /** @var static[] */
    protected static $chunks = [];

    /**
     * @return static[]
     */
    public static function all(): array
    {
        return self::$chunks;
    }

    public static function empty()
    {
        self::$chunks = [];
    }

    /**
     * @param SchemaItem $schema_item
     * @return static|null
     */
    public static function create(SchemaItem $schema_item): ?self
    {
        $object_id = spl_object_hash($schema_item);
        if (isset(static::$chunks[$object_id])) {
            return null;
        }

        return static::$chunks[$object_id] = new static($schema_item);
    }

    /**
     * @param SchemaItem $xsd_type
     * @return static|null
     */
    public static function get(SchemaItem $xsd_type): ?self
    {
        return (static::$chunks[spl_object_hash($xsd_type)] ?? null);
    }

    /** @var string */
    protected $php_namespace;
    /** @var string */
    protected $xsd_namespace;
    /** @var ChunkProperty[] */
    protected $properties = [];
    /** @var bool */
    protected $abstract;
    /** @var static|null */
    protected $parent;

    /**
     * @param string $namespace
     * @return static
     */
    public function setPHPNamespace(string $namespace): self
    {
        return $this->set('php_namespace', $namespace);
    }

    /**
     * @param string $namespace
     * @return static
     */
    public function setXSDNamespace(string $namespace): self
    {
        return $this->set('xsd_namespace', $namespace);
    }

    /**
     * @param bool $abstract
     * @return static
     */
    public function setAbstract(bool $abstract): self
    {
        return $this->set('abstract', $abstract);
    }

    /**
     * @param ChunkClass $parent
     * @return static
     */
    public function setParent(self $parent): self
    {
        return $this->set('parent', $parent);
    }

    /**
     * @param ClassProperty $property
     * @return static
     */
    public function addProperty(ClassProperty $property): self
    {
        $this->properties[] = $property;
        return $this;
    }

    public function getPhpNamespace(): string
    {
        return $this->php_namespace;
    }

    public function getXsdNamespace(): string
    {
        return $this->xsd_namespace;
    }

    /**
     * @return ChunkProperty[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return bool
     */
    public function isAbstract(): bool
    {
        return $this->abstract;
    }

    /**
     * @return static|null
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function getFullClassName(): string
    {
        return $this->php_namespace . '\\' . $this->getName();
    }

    public function getTypeName(bool $nullable = false, bool $for_annotation = false): string
    {
        return $this->getFullClassName();
    }

    public function isNative(): bool
    {
        return true;
    }

    public function isSimple(): bool
    {
        return false;
    }
}