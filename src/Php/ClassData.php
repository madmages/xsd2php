<?php

namespace Madmages\Xsd\XsdToPhp\Php;

use Madmages\Xsd\XsdToPhp\Php\Structure\PHPClass;

class ClassData implements \ArrayAccess
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

    /**
     * @param string $offset
     * @return bool
     * @throws \RuntimeException
     */
    public function offsetExists($offset): bool
    {
        $this->checkProperty($offset);
        return isset($this->$offset);
    }

    /**
     * @param string $offset
     * @return mixed
     * @throws \RuntimeException
     */
    public function offsetGet($offset)
    {
        $this->checkProperty($offset);
        return $this->$offset;
    }

    /**
     * @param string $offset
     * @param mixed $value
     * @throws \RuntimeException
     */
    public function offsetSet($offset, $value): void
    {
        $this->checkProperty($offset);

        $this->$offset = $value;
    }

    public function offsetUnset($offset): void
    {
        $this->$offset = null;
    }

    /**
     * @param string $offset
     * @throws \RuntimeException
     */
    private function checkProperty($offset)
    {
        if (!property_exists($this, $offset)) {
            throw new \RuntimeException('Offset unexists: ' . $offset);
        }
    }

    public function isSkip(): bool
    {
        return $this->skip;
    }
}