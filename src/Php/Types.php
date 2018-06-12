<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Php;

class Types
{
    public const MIXED = 'mixed';
    public const STRING = 'string';
    public const INT = 'int';
    public const FLOAT = 'float';
    public const ARRAY = 'array';
    public const OBJECT = 'object';
    public const BOOL = 'bool';
    public const CALLABLE = 'callable';
    public const SELF = 'self';
    public const VOID = 'void';
    public const STATIC = 'static';

    public static function oneOf(): string
    {
        return implode('|', func_get_args());
    }

    public static function typedArray(string $type): string
    {
        return "{$type}[]";
    }
}