<?php

namespace Madmages\Xsd\XsdToPhp\Components\Naming;

use Doctrine\Common\Inflector\Inflector;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;

class ShortNamingStrategy extends AbstractNamingStrategy
{
    public function getTypeName(Type $type): string
    {
        $name = $this->classify($type->getName());
        if ($name && substr($name, -4) !== 'Type') {
            $name .= 'Type';
        }
        return $name;
    }

    public function getAnonymousTypeName(Type $type, string $parent_name): string
    {
        return $this->classify($parent_name) . 'AType';
    }

    public function getPropertyName($item): string
    {
        return Inflector::camelize(str_replace('.', ' ', $item->getName()));
    }

    public function getItemName($item): string
    {
        $name = $this->classify($item->getName());
        if (in_array(strtolower($name), self::RESERVED_WORDS, true)) {
            $name .= 'Xsd';
        }

        return $name;
    }
}
