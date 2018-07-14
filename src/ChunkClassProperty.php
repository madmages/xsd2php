<?php

namespace Madmages\Xsd\XsdToPhp;

use Madmages\Xsd\XsdToPhp\Contract\ClassProperty;

class ChunkClassProperty extends ChunkClass implements ClassProperty
{
    use ClassPropertyTrait;

    protected $abstract = false;
}