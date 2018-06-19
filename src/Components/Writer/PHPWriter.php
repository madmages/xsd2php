<?php

namespace Madmages\Xsd\XsdToPhp\Components\Writer;

use Madmages\Xsd\XsdToPhp\FileWriter;
use Madmages\Xsd\XsdToPhp\Php\ClassGenerator;
use Madmages\Xsd\XsdToPhp\Php\Structure\PHPClass;

class PHPWriter implements FileWriter
{

    /** @var PHPClassWriter PHPClassWriter */
    protected $class_writer;
    private $generator;

    public function __construct(PHPClassWriter $class_writer, ClassGenerator $generator)
    {
        $this->generator = $generator;
        $this->class_writer = $class_writer;
    }

    /**
     * @param PHPClass[] $items
     * @throws \Zend\Code\Generator\Exception\RuntimeException
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     * @throws \RuntimeException
     */
    public function write(array $items): void
    {
        while ($item = array_pop($items)) {
            if ($zend_class = $this->generator->generateClass($item)) {
                $this->class_writer->write([$zend_class]);
            }
        }
    }
}
