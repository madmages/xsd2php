<?php

namespace Madmages\Xsd\XsdToPhp\Php\Checker;


class Length extends AbstractChecker
{
    public function __construct(string $property, array $length)
    {
        $this->code = <<<CODE
if(mb_strlen({$property}) !== {$length['value']}){
    {$this->getValidationError('expected length %s. Got %s', [$length['value'], "'.mb_strlen({$property}).'"])}
}
CODE;
    }
}