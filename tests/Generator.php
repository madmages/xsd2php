<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Tests;

use GoetasWebservices\Xsd\XsdToPhp\Jms\YamlConverter;
use GoetasWebservices\Xsd\XsdToPhp\Php\PhpConverter;

class Generator extends AbstractGenerator
{
    public function generate(array $schemas)
    {
        $this->cleanDirectories();

        $this->writeJMS($this->generateJMSFiles($schemas));
        $this->writePHP($this->generatePHPFiles($schemas));
    }

    protected function generateJMSFiles(array $schemas)
    {
        $converter = new YamlConverter($this->namingStrategy);
        $this->setNamespaces($converter);
        $items = $converter->convert($schemas);
        return $items;
    }

    protected function generatePHPFiles(array $schemas)
    {
        $converter = new PhpConverter($this->namingStrategy);
        $this->setNamespaces($converter);
        $items = $converter->convert($schemas);
        return $items;
    }

    public function getData(array $schemas)
    {
        $php = $this->generatePHPFiles($schemas);
        $jms = $this->generateJMSFiles($schemas);

        return [$php, $jms];
    }
}
