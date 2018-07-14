<?php

namespace Madmages\Xsd\XsdToPhp\Php\Checker;


class MaxInclusive extends AbstractChecker
{
    public function __construct(string $property, array $some)
    {
        return $this->inclusive($property,$some['value'],false,true);
    }
}