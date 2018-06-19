<?php

namespace Madmages\Xsd\XsdToPhp;

use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\SchemaReader;
use Illuminate\Container\Container;
use Madmages\Xsd\XsdToPhp\Components\Naming\ShortNamingStrategy;
use Madmages\Xsd\XsdToPhp\Components\Writer\JMSWriter;
use Madmages\Xsd\XsdToPhp\Components\Writer\PHPWriter;
use Madmages\Xsd\XsdToPhp\Jms\PathGenerator\Psr4PathGenerator as Psr4PathGeneratorJMS;
use Madmages\Xsd\XsdToPhp\Jms\YamlConverter;
use Madmages\Xsd\XsdToPhp\Php\PathGenerator\Psr4PathGenerator;
use Madmages\Xsd\XsdToPhp\Php\PhpConverter;

final class App extends Container
{
    public const CONVERTER = 'converter';
    public const WRITER = 'writer';
    public const PHP = 'php';
    public const JMS = 'jms';

    private static $objects = [];

    private static function initObjects()
    {
        if (empty(self::$objects)) {
            /** @var string[][] $classes */
            $classes = [
                self::PHP => [
                    self::CONVERTER => PhpConverter::class,
                    self::WRITER    => PHPWriter::class,
                ],
                self::JMS => [
                    self::CONVERTER => YamlConverter::class,
                    self::WRITER    => JMSWriter::class,
                ],
            ];

            foreach ($classes as $type => $sub_classes) {
                foreach ($sub_classes as $sub_type => $class) {
                    self::getInstance()->singleton($type . $sub_type, $class);
                }
            }
        }
    }

    /**
     * @param string $type
     * @param Schema[] $schema
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Illuminate\Container\EntryNotFoundException
     */
    public static function convertAndWrite(string $type, array $schema)
    {
        $i = self::getInstance();
        self::initObjects();

        $i->get($type . self::WRITER)->write($i->get($type . self::CONVERTER)->convert($schema));
    }

    /**
     * @param array $xsd_files
     * @param Config $config
     * @throws \Illuminate\Container\EntryNotFoundException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function run(array $xsd_files, Config $config)
    {
        $schema_reader = self::getInstance()->make(SchemaReader::class);
        self::getInstance()->instance(Config::class, $config);

        $schemas = [];
        foreach ($xsd_files as $file) {
            $schemas[] = $schema_reader->readFile($file);
        }

        self::getInstance()->singleton(NamingStrategy::class, ShortNamingStrategy::class);
        self::getInstance()->singleton(PathGenerator::class . self::PHP, Psr4PathGenerator::class);
        self::getInstance()->singleton(PathGenerator::class . self::JMS, Psr4PathGeneratorJMS::class);

        self::getInstance()->singleton(self::PHP . self::CONVERTER, PhpConverter::class);
        self::getInstance()->singleton(self::JMS . self::CONVERTER, YamlConverter::class);
        self::getInstance()->singleton(self::PHP . self::WRITER, PHPWriter::class);
        self::getInstance()->singleton(self::JMS . self::WRITER, JMSWriter::class);

        self::convertAndWrite(self::PHP, $schemas);
        self::convertAndWrite(self::JMS, $schemas);
    }
}