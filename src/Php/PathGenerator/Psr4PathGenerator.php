<?php

namespace Madmages\Xsd\XsdToPhp\Php\PathGenerator;

use Madmages\Xsd\XsdToPhp\Components\PathGenerator\Psr4PathGenerator as Psr4PathGeneratorBase;
use Madmages\Xsd\XsdToPhp\PathGenerator;
use Madmages\Xsd\XsdToPhp\Php\Structure\PHPClass;
use Zend\Code\Generator\ClassGenerator;

class Psr4PathGenerator extends Psr4PathGeneratorBase implements PathGenerator
{

    protected function getDestinations(): array
    {
        return $this->config->getDestinationPHP();
    }

    /**
     * @param ClassGenerator $zend_class
     * @return string
     * @throws \RuntimeException
     */
    public function getPath($zend_class): string
    {
        foreach ($this->destinations as $php_namespace => $destination) {
            $position = strpos(trim($zend_class->getNamespaceName()), $php_namespace);

            if ($position === 0) {
                $dir_postfix = str_replace(PHPClass::NS_SLASH, '/', substr($zend_class->getNamespaceName(), strlen($php_namespace)));
                $destination = rtrim($destination, '/') . '/' . $dir_postfix;

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