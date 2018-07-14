<?php

namespace Madmages\Xsd\XsdToPhp\Php\Checker;


class TotalDigits extends AbstractChecker
{
    public function __construct(string $property, array $length)
    {
        $this->code = <<<CODE
{$property}_1 = str_replace('.','',(string){$property});
if(strlen({$property}_1) > {$length['value']}){
    {$this->getValidationError('expected %s or less digits. Got %s', [$length['value'], "'.strlen({$property}_1).'"])}
}
CODE;
    }
}