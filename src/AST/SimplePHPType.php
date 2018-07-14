<?php

namespace Madmages\Xsd\XsdToPhp\AST;

use Madmages\Xsd\XsdToPhp\Contract\PHPType;
use Madmages\Xsd\XsdToPhp\Types;

class SimplePHPType implements PHPType
{
    private $type;
    private static $_mixed;

    public static function create(string $type): self
    {
        return new self($type);
    }

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public static function mixed(): self
    {
        if (self::$_mixed === null) {
            self::$_mixed = new self(Types::MIXED);
        }

        return self::$_mixed;
    }

    public function __toString()
    {
        return $this->type;
    }

    public function getTypeName(): string
    {
        return $this->getType();
    }

    public function isSimple(): bool
    {
        return true;
    }

    public function isNative(): bool
    {
        return $this->type !== Types::MIXED;
    }

    public function getType(): string
    {
        return $this->type;
    }
}