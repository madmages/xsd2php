<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Jms\PathGenerator;

use GoetasWebservices\Xsd\XsdToPhp\PathGenerator\PathGeneratorException;
use GoetasWebservices\Xsd\XsdToPhp\PathGenerator\Psr4PathGenerator as Psr4PathGeneratorBase;

class Psr4PathGenerator extends Psr4PathGeneratorBase implements PathGenerator
{
    /**
     * @param array $yaml
     * @return string
     * @throws PathGeneratorException
     */
    public function getPath(array $yaml): string
    {
        $ns = key($yaml);

        foreach ($this->namespaces as $namespace => $dir) {
            $pos = strpos($ns, $namespace);

            if ($pos === 0) {
                if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
                    throw new PathGeneratorException("Can't create the folder `{$dir}`");
                }

                $file_name = trim(strtr(substr($ns, strlen($namespace)), "\\/", '..'), '.');

                return "{$dir}/{$file_name}.yml";
            }
        }

        throw new PathGeneratorException("Unable to determine location to save JMS metadata for class `{$ns}`");
    }
}

