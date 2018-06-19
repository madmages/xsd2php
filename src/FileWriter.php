<?php

namespace Madmages\Xsd\XsdToPhp;

interface FileWriter
{
    public function write(array $items): void;
}