<?php

namespace Madmages\Xsd\XsdToPhp\Components\Writer;

use Madmages\Xsd\XsdToPhp\FileWriter;
use Madmages\Xsd\XsdToPhp\Php\ClassGenerator;
use Madmages\Xsd\XsdToPhp\Php\PathGenerator\Psr4PathGenerator;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Zend\Code\Generator\FileGenerator;

class PHPClassWriter implements FileWriter
{
    use LoggerAwareTrait;

    protected $pathGenerator;

    public function __construct(Psr4PathGenerator $pathGenerator, LoggerInterface $logger = null)
    {
        $this->pathGenerator = $pathGenerator;
        $this->logger = $logger ?: new NullLogger();
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

            $this->logger->debug(sprintf('Written PHP class file %s', $path));
        }

        $this->logger->info(sprintf('Written %s STUB classes', count($items)));
    }
}
