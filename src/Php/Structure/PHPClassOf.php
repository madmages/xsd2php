<?php

namespace Madmages\Xsd\XsdToPhp\Php\Structure;

class PHPClassOf extends PHPClass
{
    /**  @var PHPProperty */
    protected $arg;

    /**
     * @param PHPProperty $arg
     */
    public function __construct(PHPProperty $arg)
    {
        $this->arg = $arg;
        $this->name = 'array';
    }

    /**
     * @return PHPProperty
     */
    public function getArg(): PHPProperty
    {
        return $this->arg;
    }
}

