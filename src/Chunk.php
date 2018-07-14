<?php

namespace Madmages\Xsd\XsdToPhp;

use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeDef;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Group as AttributeGroup;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
use GoetasWebservices\XML\XSDReader\Schema\Element\Group as ElementGroup;
use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;
use Madmages\Xsd\XsdToPhp\AST\Attribute;
use Madmages\Xsd\XsdToPhp\AST\Element;

class Chunk
{
    private $debug = false;
    private $trace;

    /** @var string */
    private $name;
    /** @var ElementDef|Element|Attribute|AttributeDef|ElementGroup|AttributeGroup */
    private $node;
    /** @var string */
    private $comment;

    protected function __construct(SchemaItem $node)
    {
        $this->node = $node;

        if ($this->debug) {
            $this->trace = debug_backtrace(null, 20);
        }
    }

    /**
     * @param string $name
     * @return static
     */
    public function setName(string $name): self
    {
        return $this->set('name', $name);
    }

    /**
     * @param string $comment
     * @return static
     */
    public function setComment(string $comment): self
    {
        return $this->set('comment', $comment);
    }

    /**
     * @param string $property
     * @param $value
     * @return static
     */
    protected function set(string $property, $value): self
    {
        $this->$property = $value;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @return array
     */
    public function getTrace(): array
    {
        return $this->trace;
    }

    /**
     * @return AttributeDef|AttributeGroup|ElementDef|ElementGroup|Attribute|Element
     */
    public function getNode(): SchemaItem
    {
        return $this->node;
    }
}