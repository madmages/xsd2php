<?php

namespace Madmages\Xsd\XsdToPhp\Php\Checker;


class Enumiration extends AbstractChecker
{
    public function __construct()
    {
        $args = func_get_args();
        $property = array_shift($args);

        $array = array_map(function ($item) {
            return "\"{$item['value']}\"";
        }, $args);

        $items = implode(',', $array);
        $items_array_string = "[{$items}]";

        $this->code = <<<CODE
if(!in_array({$property},{$items_array_string})){
    {$this->getValidationError('expected value to be one of %s. Got %s', [$items, "'.{$property}.'"])}
}
CODE;
    }
}