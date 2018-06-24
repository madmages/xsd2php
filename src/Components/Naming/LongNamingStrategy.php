<?php

namespace Madmages\Xsd\XsdToPhp\Components\Naming;

use Doctrine\Common\Inflector\Inflector;

class LongNamingStrategy extends AbstractNamingStrategy
{
    public function getTypeName(string $type): string
    {
        return $this->classify($type) . 'Type';
    }

    public function getAnonymousTypeName(string $type, string $parent_name): string
    {
        return $this->classify($parent_name) . 'AnonymousType';
    }

    public function getItemName(string $item): string
    {
        $name = $this->classify($item);
        if (in_array(strtolower($name), self::RESERVED_WORDS, true)) {
            $name .= 'Xsd';
        }
        return $name;
    }

    public function getPropertyName(string $item): string
    {
        return Inflector::camelize(str_replace('.', ' ', $item));
    }
}
