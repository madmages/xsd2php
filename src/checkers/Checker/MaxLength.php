<?php

namespace Madmages\Xsd\XsdToPhp\Php\Checker;


class MaxLength extends AbstractChecker
{
    public function __construct(string $property, array $max_length)
    {
        $this->code = <<<CODE
if(mb_strlen({$property}) > {$max_length['value']}){
    {$this->getValidationError('expected length %s or lower. Got %s', [$max_length['value'], "'.mb_strlen({$property}).'"])}
}
CODE;
    }
}