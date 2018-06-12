<?php

namespace Madmages\Xsd\XsdToPhp\Jms\PathGenerator;

interface PathGenerator
{
    public function getPath(array $yaml): string;
}