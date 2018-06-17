<?php

namespace Madmages\Xsd\XsdToPhp\Components\Writer;

use Madmages\Xsd\XsdToPhp\FileWriter;
use Madmages\Xsd\XsdToPhp\Jms\PathGenerator\Psr4PathGenerator;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Yaml\Dumper;

class JMSWriter implements FileWriter
{
    use LoggerAwareTrait;
    private $pathGenerator;

    public function __construct(Psr4PathGenerator $pathGenerator, LoggerInterface $logger = null)
    {
        $this->pathGenerator = $pathGenerator;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * @param array $items
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function write(array $items): void
    {
        $dumper = new Dumper();
        foreach ($items as $item) {
            $source = $dumper->dump($item, 10000);
            $path = $this->pathGenerator->getPath($item);
            file_put_contents($path, $source);
            $this->logger->debug(sprintf('Written JMS metadata file %s', $path));
        }
        $this->logger->info(sprintf('Written %s JMS metadata files ', count($items)));
    }
}
