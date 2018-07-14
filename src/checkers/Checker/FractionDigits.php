<?php

namespace Madmages\Xsd\XsdToPhp\Php\Checker;


class FractionDigits extends AbstractChecker
{
    public function __construct(string $property, array $length)
    {
        $this->code = <<<CODE
{$property}_1 = explode('.', (string){$property})[1] ?? 0;
if(strlen({$property}_1) > {$length['value']}){
    {$this->getValidationError('expected %s or less digits. Got %s', [$length['value'], "'.strlen({$property}_1).'"])}
}
CODE;
    }
}