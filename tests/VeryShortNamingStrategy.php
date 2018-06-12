<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Tests;

use Doctrine\Common\Inflector\Inflector;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;
use GoetasWebservices\Xsd\XsdToPhp\Naming\ShortNamingStrategy;

/**
 * The OTA psr4 class paths can exceed windows max dir length
 */
class VeryShortNamingStrategy extends ShortNamingStrategy
{
    /**
     * Suffix with 'T' instead of 'Type'
     * @param Type $type
     * @return string
     */
    public function getTypeName(Type $type)
    {
        $name = $this->classify($type->getName());

        if ($name && substr($name, -4) !== 'Type') {
            return $name . 'T';
        }

        if (substr($name, -4) === 'Type') {
            return substr($name, 0, -3);
        }

        return $name;
    }

    /**
     * @param string $name
     * @return string
     */
    private function classify($name)
    {
        return Inflector::classify(str_replace(".", " ", $name));
    }

    /**
     * Suffix with 'A' instead of 'AType'
     * @param Type $type
     * @param string $parentName
     * @return string
     */
    public function getAnonymousTypeName(Type $type, $parentName)
    {
        return $this->classify($parentName) . 'A';
    }
}
