<?php

namespace Madmages\Xsd\XsdToPhp\Components\PathGenerator;

abstract class Psr4PathGenerator
{

    protected $destinations = [];

    abstract protected function getDestinations(): array;

    /**
     * Psr4PathGenerator constructor.
     * @throws \RuntimeException
     */
    public function __construct()
    {
        $this->setTargets($this->getDestinations());
    }

    /**
     * @param $destinations
     * @throws \RuntimeException
     */
    public function setTargets(array $destinations): void
    {
        $this->destinations = $destinations;

        foreach ($this->destinations as $php_namespace => $directory) {
            if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
            }
        }
    }
}

