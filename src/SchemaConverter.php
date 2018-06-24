<?php

namespace Madmages\Xsd\XsdToPhp;

interface SchemaConverter
{
    public function convert(array $schemas);
}