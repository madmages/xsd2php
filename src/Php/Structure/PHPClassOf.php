<?php

namespace Madmages\Xsd\XsdToPhp\Php\Structure;

class PHPClassOf extends PHPClass
{
    /**  @var PHPArg */
    protected $arg;

    /**
     * @param PHPArg $arg
     */
    public function __construct(PHPArg $arg)
    {
        $this->arg = $arg;
        $this->name = 'array';
    }

    /**
     * @return PHPArg
     */
    public function getArg(): PHPArg
    {
        return $this->arg;
    }
}

