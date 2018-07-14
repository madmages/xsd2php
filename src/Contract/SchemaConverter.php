<?php

namespace Madmages\Xsd\XsdToPhp\Contract;

interface SchemaConverter
{
    public function convert(array $schemas);
}