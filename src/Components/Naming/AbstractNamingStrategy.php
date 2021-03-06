<?php

namespace Madmages\Xsd\XsdToPhp\Components\Naming;

use Doctrine\Common\Inflector\Inflector;
use Madmages\Xsd\XsdToPhp\Contract\NamingStrategy;

abstract class AbstractNamingStrategy implements NamingStrategy
{
    protected const RESERVED_WORDS = [
        '__halt_compiler',
        'abstract',
        'and',
        'array',
        'as',
        'bool',
        'break',
        'callable',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'die',
        'do',
        'echo',
        'else',
        'elseif',
        'empty',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'eval',
        'exit',
        'extends',
        'false',
        'final',
        'float',
        'for',
        'foreach',
        'function',
        'global',
        'goto',
        'if',
        'implements',
        'include',
        'include_once',
        'instanceof',
        'insteadof',
        'int',
        'interface',
        'isset',
        'list',
        'mixed',
        'namespace',
        'new',
        'null',
        'numeric',
        'object',
        'or',
        'print',
        'private',
        'protected',
        'public',
        'require',
        'require_once',
        'resource',
        'return',
        'static',
        'string',
        'switch',
        'throw',
        'trait',
        'true',
        'try',
        'unset',
        'use',
        'var',
        'while',
        'xor',
    ];

    public function getSetterMethod(string $property_name): string
    {
        return 'set' . $this->classifyMethod($property_name);
    }

    protected function classifyMethod(string $item): string
    {
        return Inflector::classify($item);
    }

    public function getGetterMethod(string $property_name): string
    {
        return 'get' . $this->classifyMethod($property_name);
    }

    protected function classify(?string $name): string
    {
        return Inflector::classify(str_replace('.', ' ', $name ?? ''));
    }
}