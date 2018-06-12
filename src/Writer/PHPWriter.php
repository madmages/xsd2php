<?php

namespace Madmages\Xsd\XsdToPhp\Writer;

use Madmages\Xsd\XsdToPhp\Php\ClassGenerator;
use Madmages\Xsd\XsdToPhp\Php\Structure\PHPClass;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PHPWriter extends Writer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected $classWriter;
    private $generator;

    public function __construct(PHPClassWriter $classWriter, ClassGenerator $generator, LoggerInterface $logger = null)
    {
        $this->generator = $generator;
        $this->classWriter = $classWriter;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * @param PHPClass[] $items
     */
    public function write(array $items)
    {
        while ($item = array_pop($items)) {
            if ($zend_class = $this->generator->generateClass($item)) {
                $this->classWriter->write([$zend_class]);
            }
        }
    }
}
