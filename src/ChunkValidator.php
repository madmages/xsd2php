<?php

namespace Madmages\Xsd\XsdToPhp;

use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;
use Madmages\Xsd\XsdToPhp\Contract\PHPType;
use RuntimeException;

class ChunkValidator extends Chunk implements PHPType
{
    /** @var static[] */
    protected static $chunks = [];

    /**
     * @return static[]
     */
    public static function getChunks(): array
    {
        return self::$chunks;
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

    /** @var string[][] */
    protected $checks = [];
    /** @var string[][] */
    protected $unions = [];
    /** @var bool */
    protected $list = false;
    /** @var PHPType */
    protected $type;
    /** @var self|null */
    protected $parent;
    /** @var string */
    protected $php_namespace;
    /** @var string */
    protected $xsd_namespace;

    public static function empty()
    {
        self::$chunks = [];
    }

    public function addChecks(array $checks): self
    {
        $this->checks[] = $checks;
        return $this;
    }

    public function setUnions(array $unions): self
    {
        return $this->set('unions', $unions);
    }

    public function setList(bool $list = true): self
    {
        return $this->set('list', $list);
    }

    public function getList(): bool
    {
        return $this->list;
    }

    public function getChecks(): array
    {
        return $this->checks;
    }

    public function getUnions(): array
    {
        return $this->unions;
    }

    /**
     * @return PHPType
     */
    public function getType(): ?PHPType
    {
        return $this->type;
    }

    /**
     * @param PHPType $type
     * @return static
     */
    public function setType(PHPType $type): self
    {
        return $this->set('type', $type);
    }

    /**
     * @param self $parent
     * @return static
     */
    public function setParent(self $parent): self
    {
        return $this->set('parent', $parent);
    }

    /**
     * @return static|null
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

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

    public function getPhpNamespace(): string
    {
        return $this->php_namespace;
    }

    public function getXsdNamespace(): string
    {
        return $this->xsd_namespace;
    }

    /**
     * @return string
     * @throws RuntimeException
     */
    public function getTypeName(): string
    {
        return $this->recursiveGetSimpleType($this);
    }

    public function isSimple(): bool
    {
        return true;
    }

    public function isNative(): bool
    {
        return true;
    }

    /**
     * @param ChunkValidator $validator
     * @return string
     * @throws \RuntimeException
     */
    private function recursiveGetSimpleType(self $validator): string
    {
        /** @var self $type */
        $type = $validator->getType();
        if ($type === null) {
            throw new RuntimeException('unexpected');
        }

        if (in_array($type->getTypeName(), Types::SIMPLE_TYPES, true)) {
            return $type->getTypeName();
        }

        /** @var self $parent_type */
        if (!$parent_type = $type->getType()) {
            throw new RuntimeException('unexpected');
        }

        return $this->recursiveGetSimpleType($parent_type);
    }

    /**
     * @param ChunkValidator|null $target
     * @return self[]
     */
    public function flatten(self $target = null): array
    {
        if ($target === null) {
            $target = $this;
        }

        $result = [$target];
        if ($target->parent) {
            $result = array_merge($result, $this->flatten($this->parent));
        }

        return $result;
    }
}