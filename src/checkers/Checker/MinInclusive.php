<?php

namespace Madmages\Xsd\XsdToPhp\Php\Checker;


class MinInclusive extends AbstractChecker
{
    public function __construct(string $property, array $some)
    {
        return $this->inclusive($property,$some['value'],true,true);
    }
}