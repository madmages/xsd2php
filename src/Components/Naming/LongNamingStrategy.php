<?php

namespace Madmages\Xsd\XsdToPhp\Components\Naming;

use Doctrine\Common\Inflector\Inflector;
use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;

class LongNamingStrategy extends AbstractNamingStrategy
{
    public function getTypeName(Type $type): string
    {
        return $this->classify($type->getName()) . 'Type';
    }

    public function getAnonymousTypeName(Type $type, string $parent_name): string
    {
        return $this->classify($parent_name) . 'AnonymousType';
    }

    public function getItemName(Item $item): string
    {
        $name = $this->classify($item->getName());
        if (in_array(strtolower($name), self::RESERVED_WORDS, true)) {
            $name .= 'Xsd';
        }
        return $name;
    }

    public function getPropertyName($item): string
    {
        return Inflector::camelize(str_replace('.', ' ', $item->getName()));
    }
}
