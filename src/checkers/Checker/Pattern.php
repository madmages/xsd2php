<?php

namespace Madmages\Xsd\XsdToPhp\Php\Checker;


class Pattern extends AbstractChecker
{
    public function __construct(string $property, array $pattern)
    {
        $pattern_string = "'/{$pattern['value']}/iU'";
        $this->code = <<<CODE
if(!preg_match({$pattern_string},{$property})){
    {$this->getValidationError('expected value match pattern "%s"', [$pattern['value']])}
}
CODE;
    }
}