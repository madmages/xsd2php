<?php

namespace Madmages\Xsd\XsdToPhp\Writer;

abstract class Writer
{
    public abstract function write(array $items);
}