<?php

namespace Madmages\Xsd\XsdToPhp;

class Config
{
    private static $configs = [
        'namespaces'       => [
            'http://zakupki.gov.ru/223fz/types/1' => 'ZGR',
        ],
        'destinations_php' => [
            'ZGR' => 'dest',
        ],
        'destinations_jms' => [
            'ZGR' => 'metadata',
        ],
        'aliases'          => [
            'http://www.example.org/test/' =>
                [
                    'MyCustomXSDType' => 'MyCustomMappedPHPType',
                ],
        ],
        'naming_strategy'  => 'short',
        'path_generator'   => 'psr4',
    ];

    public static function addNamespace(string $xsd_namespace, string $php_namespace): void
    {
        self::$configs['namespaces'][$xsd_namespace] = $php_namespace;
    }

    /**
     * @return string[]
     */
    public static function getNamespaces(): array
    {
        return self::$configs['namespaces'];
    }

    public static function addDestinationPHP(string $php_namespace, string $path): void
    {
        self::$configs['destinations_php'][$php_namespace] = $path;
    }

    /**
     * @return string[]
     */
    public static function getDestinationPHP(): array
    {
        return self::$configs['destinations_php'];
    }

    public static function addDestinationJMS(string $php_namespace, string $path): void
    {
        self::$configs['destinations_php'][$php_namespace] = $path;
    }

    /**
     * @return string[]
     */
    public static function getDestinationJMS(): array
    {
        return self::$configs['destinations_php'];
    }
}