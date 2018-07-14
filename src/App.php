<?php

namespace Madmages\Xsd\XsdToPhp;

use GoetasWebservices\XML\XSDReader\SchemaReader;
use Illuminate\Container\Container;
use Madmages\Xsd\XsdToPhp\Components\Writer\JMSWriter;
use Madmages\Xsd\XsdToPhp\Components\Writer\PHPClassWriter;
use Madmages\Xsd\XsdToPhp\Handler\Jms;
use Madmages\Xsd\XsdToPhp\Handler\Php;

final class App extends Container
{
    /**
     * @param string[] $xsd_files
     * @param Config $config
     * @return App
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function run(array $xsd_files, Config $config): self
    {
        return (new self())
            ->init($config)
            ->process($xsd_files, $config);
    }

    private function init(Config $config)
    {
        $this->instance(Config::class, $config);
        $this->singleton(SchemaReader::class);
        $this->singleton(Contract\NamingStrategy::class, $config->getNamingStrategy());
        $this->singleton(Contract\PathGenerator::class, $config->getPathGenerator());

        return $this;
    }

    /**
     * @param string[] $xsd_files
     * @param Config $config
     * @return App
     */
    private function process(array $xsd_files, Config $config): self
    {
        ChunkClass::empty();
        ChunkValidator::empty();

        $schema_reader = $this->make(SchemaReader::class);

        $php_classes = [];
        foreach ($xsd_files as $file) {
            $php_classes[] = $schema_reader->readFile($file);
        }

        $chunks = $this->make(Converter::class)->process($php_classes);

        $php = $this->make(Php::class);
        $classes = [];
        foreach ($chunks as $chunk) {
            $classes[] = $php->handle($chunk);
        }

        $jms = $this->make(Jms::class);
        $jms_classes = [];
        foreach ($chunks as $chunk) {
            $jms_classes[] = $jms->handle($chunk);
        }

        $this->make(PHPClassWriter::class)->write($classes);
        $this->make(JMSWriter::class)->write($jms_classes);

        return $this;
    }
}