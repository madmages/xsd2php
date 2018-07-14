<?php

namespace Madmages\Xsd\XsdToPhp\Components\Naming;

use Doctrine\Common\Inflector\Inflector;

class LongNamingStrategy extends AbstractNamingStrategy
{
    public function getTypeName(string $type_name): string
    {
        return $this->classify($type_name) . 'Type';
    }

    public function getElementName(string $element_name): string
    {
        return $this->classify($element_name) . 'Element';
    }

    public function getAnonymousTypeName(string $parent_name): string
    {
        return $this->classify($parent_name) . 'AnonymousType';
    }

    public function getItemName(string $item_name): string
    {
        $item_name = $this->classify($item_name);
        if (in_array(strtolower($item_name), self::RESERVED_WORDS, true)) {
            $item_name .= 'Xsd';
        }
        return $item_name;
    }

    public function getPropertyName(string $property_name): string
    {
        return Inflector::camelize(str_replace('.', ' ', $property_name));
    }
}
