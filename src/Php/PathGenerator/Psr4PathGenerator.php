<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Php\PathGenerator;

use GoetasWebservices\Xsd\XsdToPhp\PathGenerator\PathGeneratorException;
use GoetasWebservices\Xsd\XsdToPhp\PathGenerator\Psr4PathGenerator as Psr4PathGeneratorBase;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass;
use Zend\Code\Generator\ClassGenerator;

class Psr4PathGenerator extends Psr4PathGeneratorBase implements PathGenerator
{
    /**
     * @param ClassGenerator $php
     * @return string
     * @throws PathGeneratorException
     */
    public function getPath(ClassGenerator $php): string
    {
        foreach ($this->namespaces as $namespace => $dir) {
            if (strpos(trim($php->getNamespaceName()) . PHPClass::NS_SLASH, $namespace) === 0) {
                $dir_postfix = str_replace(PHPClass::NS_SLASH, '/', substr($php->getNamespaceName(), strlen($namespace)));
                $dir = rtrim($dir, '/') . '/' . $dir_postfix;

                if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
                    $error = error_get_last();
                    throw new PathGeneratorException("Can't create the '{$dir}' directory: '{$error['message']}'");
                }

                return rtrim($dir, '/') . '/' . $php->getName() . '.php';
            }
        }

        throw new PathGeneratorException("Unable to determine location to save PHP class '" . $php->getNamespaceName() . PHPClass::NS_SLASH . $php->getName() . "'");
    }
}

