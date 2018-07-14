<?php

namespace Madmages\Xsd\XsdToPhp\Php\Checker;


class MinLength extends AbstractChecker
{
    public function __construct(string $property, array $min_length)
    {
        $this->code = <<<CODE
if(mb_strlen({$property}) < {$min_length['value']}){
    {$this->getValidationError('expected length %s or more. Got %s', [$min_length['value'], "'.mb_strlen({$property}).'"])}
}
CODE;
    }
}