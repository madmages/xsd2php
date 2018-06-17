<?php

namespace Madmages\Xsd\XsdToPhp;

interface PathGenerator
{
    public function getPath($item): string;
}