<?php

namespace Madmages\Xsd\XsdToPhp\Tests;

use GoetasWebservices\XML\XSDReader\Schema\Type\Type;
use Madmages\Xsd\XsdToPhp\Components\Naming\ShortNamingStrategy;

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
    public function getTypeName(Type $type): string
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
     * Suffix with 'A' instead of 'AType'
     * @param Type $type
     * @param string $parent_name
     * @return string
     */
    public function getAnonymousTypeName(Type $type, $parent_name): string
    {
        return $this->classify($parent_name) . 'A';
    }
}
