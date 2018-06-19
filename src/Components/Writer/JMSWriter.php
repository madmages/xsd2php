<?php

namespace Madmages\Xsd\XsdToPhp\Components\Writer;

use Madmages\Xsd\XsdToPhp\FileWriter;
use Madmages\Xsd\XsdToPhp\Jms\PathGenerator\Psr4PathGenerator;
use Symfony\Component\Yaml\Dumper;

class JMSWriter implements FileWriter
{
    private $pathGenerator;

    public function __construct(Psr4PathGenerator $pathGenerator)
    {
        $this->pathGenerator = $pathGenerator;
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
        }
    }
}
