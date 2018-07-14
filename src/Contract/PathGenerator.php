<?php

namespace Madmages\Xsd\XsdToPhp\Contract;

use Zend\Code\Generator\ClassGenerator;

interface PathGenerator
{
    public function getJMSPath(array $yaml): string;

    public function getPHPPath(ClassGenerator $zend_class): string;
}