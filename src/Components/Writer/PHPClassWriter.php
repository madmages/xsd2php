<?php

namespace Madmages\Xsd\XsdToPhp\Components\Writer;

use Madmages\Xsd\XsdToPhp\Contract\PathGenerator;
use Madmages\Xsd\XsdToPhp\Php\ClassGenerator;
use Zend\Code\Generator\FileGenerator;

class PHPClassWriter
{
    protected $path_generator;

    /**
     * @param PathGenerator $generator
     */
    public function __construct(PathGenerator $generator)
    {
        $this->path_generator = $generator;
    }

    /**
     * @param ClassGenerator[] $items
     * @throws \Zend\Code\Generator\Exception\RuntimeException
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     * @throws \RuntimeException
     */
    public function write(array $items): void
    {
        foreach ($items as $item) {
            $path = $this->path_generator->getPHPPath($item);

            (new FileGenerator())
                ->setFilename($path)
                ->setClass($item)
                ->write();
        }
    }
}
