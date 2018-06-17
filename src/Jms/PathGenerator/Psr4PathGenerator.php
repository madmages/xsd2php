<?php

namespace Madmages\Xsd\XsdToPhp\Jms\PathGenerator;

use Madmages\Xsd\XsdToPhp\Components\PathGenerator\Psr4PathGenerator as Psr4PathGeneratorBase;
use Madmages\Xsd\XsdToPhp\Config;
use Madmages\Xsd\XsdToPhp\PathGenerator;

class Psr4PathGenerator extends Psr4PathGeneratorBase implements PathGenerator
{
    protected function getDestinations(): array
    {
        return Config::getDestinationJMS();
    }

    /**
     * @param array $yaml
     * @return string
     * @throws \RuntimeException
     */
    public function getPath($yaml): string
    {
        $current_yaml_ns = key($yaml);

        foreach ($this->destinations as $namespace_php => $directory_path) {
            // Directory path should be substring of php namespace
            if (strpos($current_yaml_ns, $namespace_php) === 0) {
                if (!is_dir($directory_path) && !mkdir($directory_path, 0777, true) && !is_dir($directory_path)) {
                    throw new \RuntimeException(sprintf('Can`t create the folder `%s`', $directory_path));
                }

                $file_name = trim(strtr(substr($current_yaml_ns, strlen($directory_path)), "\\/", '..'), '.');

                return "{$directory_path}/{$file_name}.yml";
            }
        }

        throw new \RuntimeException(sprintf('Unable to determine location to save JMS metadata for class `%s`', $current_yaml_ns));
    }
}