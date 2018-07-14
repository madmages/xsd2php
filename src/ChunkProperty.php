<?php

namespace Madmages\Xsd\XsdToPhp;

use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;
use Madmages\Xsd\XsdToPhp\Contract\ClassProperty;

class ChunkProperty extends Chunk implements ClassProperty
{
    use ClassPropertyTrait;

    public static function create(SchemaItem $node): self
    {
        return new static($node);
    }
}