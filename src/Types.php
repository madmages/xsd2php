<?php

namespace Madmages\Xsd\XsdToPhp;

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
    public const NULL = 'null';

    public const SIMPLE_TYPES = [
        self::MIXED,
        self::STRING,
        self::INT,
        self::FLOAT,
        self::ARRAY,
        self::OBJECT,
        self::BOOL,
        self::CALLABLE,
        self::SELF,
        self::VOID,
        self::STATIC,
        self::NULL,
    ];

    public const F_NULL = 2 ** 0;
    public const F_DOC = 2 ** 1;
    public const F_ARRAY = 2 ** 2;

    public static function typedArray(string $type): string
    {
        return "{$type}[]";
    }

    public static function format(string $type, int $flags = 0): ?string
    {
        if (
            $flags & self::F_DOC
            && !in_array($type, self::SIMPLE_TYPES, true)
        ) {
            $type = "\\{$type}";
        }

        if ($type === self::STATIC) {
            if (!($type & self::F_DOC)) {
                return null;
            }
        }

        if ($flags & self::F_ARRAY) {
            if ($flags & self::F_DOC) {
                $type .= '[]';
            } else {
                $type = self::ARRAY;
            }
        }

        if ($flags & self::F_NULL) {
            if ($flags & self::F_DOC) {
                $type = "$type|" . self::NULL;
            } else {
                $type = "?$type";
            }
        }

        return $type;
    }
}