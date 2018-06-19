<?php

namespace Madmages\Xsd\XsdToPhp\Components\Writer;

use Madmages\Xsd\XsdToPhp\FileWriter;
use Madmages\Xsd\XsdToPhp\Php\ClassGenerator;
use Madmages\Xsd\XsdToPhp\Php\PathGenerator\Psr4PathGenerator;
use Zend\Code\Generator\FileGenerator;

class PHPClassWriter implements FileWriter
{

    protected $pathGenerator;

    public function __construct(Psr4PathGenerator $pathGenerator)
    {
        $this->pathGenerator = $pathGenerator;
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
            $path = $this->pathGenerator->getPath($item);

            (new FileGenerator())
                ->setFilename($path)
                ->setClass($item)
                ->write();
        }
    }
}
