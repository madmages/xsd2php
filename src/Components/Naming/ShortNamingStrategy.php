<?php

namespace Madmages\Xsd\XsdToPhp\Components\Naming;

use Doctrine\Common\Inflector\Inflector;

class ShortNamingStrategy extends AbstractNamingStrategy
{
    public function getTypeName(string $type_name): string
    {
        $type_name = $this->classify($type_name);
        if ($type_name && substr($type_name, -4) !== 'Type') {
            $type_name .= 'Type';
        }
        return $type_name;
    }

    public function getAnonymousTypeName(string $parent_name): string
    {
        return $this->classify($parent_name) . 'AType';
    }

    public function getPropertyName(string $property_name): string
    {
        return Inflector::camelize(str_replace('.', ' ', $property_name));
    }

    public function getItemName(string $item_name): string
    {
        $item_name = $this->classify($item_name);
        if (in_array(strtolower($item_name), self::RESERVED_WORDS, true)) {
            $item_name .= 'Xsd';
        }

        return $item_name;
    }

    public function getElementName(string $element_name): string
    {
        return $element_name;
    }
}
