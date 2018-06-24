<?php

namespace Madmages\Xsd\XsdToPhp\Components\PathGenerator;

use Illuminate\Container\Container;
use Madmages\Xsd\XsdToPhp\Config;
use Madmages\Xsd\XsdToPhp\PathGenerator;
use Madmages\Xsd\XsdToPhp\Php\Structure\PHPClass;
use Zend\Code\Generator\ClassGenerator;

class Psr4 implements PathGenerator
{
    private const JMS = 'jms';
    private const PHP = 'php';

    protected $destinations = [];
    protected $config;

    /**
     * Psr4PathGenerator constructor.
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \RuntimeException
     * @throws \Illuminate\Container\EntryNotFoundException
     */
    public function __construct()
    {
        /** @var Config $config */
        $config = Container::getInstance()->get(Config::class);

        $this->setTargets($config->getDestinationJMS(), self::JMS);
        $this->setTargets($config->getDestinationPHP(), self::PHP);
    }

    /**
     * @param array $destinations
     * @param string $type
     * @throws \RuntimeException
     */
    public function setTargets(array $destinations, string $type): void
    {
        $this->destinations[$type] = $destinations;

        foreach ($this->destinations[$type] as $php_namespace => $directory) {
            if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
            }
        }
    }


    /**
     * @param array $yaml
     * @return string
     * @throws \RuntimeException
     */
    public function getJMSPath(array $yaml): string
    {
        $current_yaml_ns = key($yaml);

        foreach ($this->destinations[self::JMS] as $namespace_php => $directory_path) {
            // Directory path should be substring of php namespace
            if (strpos($current_yaml_ns, $namespace_php) === 0) {
                if (!is_dir($directory_path) && !mkdir($directory_path, 0777, true) && !is_dir($directory_path)) {
                    throw new \RuntimeException(sprintf('Can`t create the folder `%s`', $directory_path));
                }

                $file_name = trim(strtr(substr($current_yaml_ns, strlen($namespace_php)), "\\/", '..'), '.');

                return "{$directory_path}/{$file_name}.yml";
            }
        }

        throw new \RuntimeException(sprintf('Unable to determine location to save JMS metadata for class `%s`', $current_yaml_ns));
    }

    /**
     * @param ClassGenerator $zend_class
     * @return string
     * @throws \RuntimeException
     */
    public function getPHPPath(ClassGenerator $zend_class): string
    {
        foreach ($this->destinations[self::PHP] as $php_namespace => $destination) {
            $position = strpos(trim($zend_class->getNamespaceName()), $php_namespace);

            if ($position === 0) {
                $dir_postfix = str_replace(PHPClass::NS_SLASH, '/', substr($zend_class->getNamespaceName(), strlen($php_namespace)));
                $destination = rtrim($destination, '/') . $dir_postfix;

                if (!is_dir($destination) && !mkdir($destination, 0777, true) && !is_dir($destination)) {
                    $error = error_get_last();
                    throw new \RuntimeException("Can't create the '{$destination}' directory: '{$error['message']}'");
                }

                return rtrim($destination, '/') . '/' . $zend_class->getName() . '.php';
            }
        }

        throw new \RuntimeException("Unable to determine location to save PHP class '" . $zend_class->getNamespaceName() . PHPClass::NS_SLASH . $zend_class->getName() . "'");
    }
}