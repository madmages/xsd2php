<?php

namespace Madmages\Xsd\XsdToPhp\Components\Writer;

use Madmages\Xsd\XsdToPhp\PathGenerator;
use Symfony\Component\Yaml\Dumper;

class JMSWriter
{
    private $path_generator;

    /**
     * @param PathGenerator $generator
     */
    public function __construct(PathGenerator $generator)
    {
        $this->path_generator = $generator;
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
            $path = $this->path_generator->getJMSPath($item);
            file_put_contents($path, $source);
        }
    }
}
