<?php

namespace Madmages\Xsd\XsdToPhp;

use GoetasWebservices\XML\XSDReader\Schema\Schema;
use Illuminate\Container\Container;
use Madmages\Xsd\XsdToPhp\Components\Writer\JMSWriter;
use Madmages\Xsd\XsdToPhp\Components\Writer\PHPWriter;
use Madmages\Xsd\XsdToPhp\Jms\YamlConverter;
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
}