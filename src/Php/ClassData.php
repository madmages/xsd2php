<?php

namespace Madmages\Xsd\XsdToPhp\Php;

use Madmages\Xsd\XsdToPhp\Php\Structure\PHPClass;

class ClassData
{
    /** @var PHPClass */
    private $class;

    /** @var bool */
    private $skip;

    public function __construct(PHPClass $class, bool $skip = false)
    {
        $this->class = $class;
        $this->skip = $skip;
    }

    public function skip(bool $skip = true)
    {
        $this->skip = $skip;
    }

    public function getClass(): PHPClass
    {
        return $this->class;
    }

    public function isSkip(): bool
    {
        return $this->skip;
    }
}