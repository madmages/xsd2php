<?php

namespace Madmages\Xsd\XsdToPhp;

use GoetasWebservices\XML\XSDReader\SchemaReader;
use Illuminate\Container\Container;
use Madmages\Xsd\XsdToPhp\Components\Writer\JMSWriter;
use Madmages\Xsd\XsdToPhp\Components\Writer\PHPWriter;
use Madmages\Xsd\XsdToPhp\Jms\YamlConverter;
use Madmages\Xsd\XsdToPhp\Php\PhpConverter;

final class App extends Container
{
    /**
     * @param array $xsd_files
     * @param Config $config
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function run(array $xsd_files, Config $config)
    {
        self::setInstance();

        $i = self::getInstance();
        $i->instance(Config::class, $config);

        $schema_reader = $i->make(SchemaReader::class);
        $schemas = [];
        foreach ($xsd_files as $file) {
            $schemas[] = $schema_reader->readFile($file);
        }

        $i->singleton(NamingStrategy::class, $config->getNamingStrategy());
        $i->singleton(PathGenerator::class, $config->getPathGenerator());

        $i->make(PHPWriter::class)->write($i->make(PhpConverter::class)->convert($schemas));
        $i->make(JMSWriter::class)->write($i->make(YamlConverter::class)->convert($schemas));
    }
}