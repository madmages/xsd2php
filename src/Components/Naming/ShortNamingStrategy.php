<?php

namespace Madmages\Xsd\XsdToPhp\Components\Naming;

use Doctrine\Common\Inflector\Inflector;

class ShortNamingStrategy extends AbstractNamingStrategy
{
    public function getTypeName(string $type): string
    {
        $name = $this->classify($type);
        if ($name && substr($name, -4) !== 'Type') {
            $name .= 'Type';
        }
        return $name;
    }

    public function getAnonymousTypeName(string $type, string $parent_name): string
    {
        return $this->classify($parent_name) . 'AType';
    }

    public function getPropertyName(string $item): string
    {
        return Inflector::camelize(str_replace('.', ' ', $item));
    }

    public function getItemName(string $item): string
    {
        $name = $this->classify($item);
        if (in_array(strtolower($name), self::RESERVED_WORDS, true)) {
            $name .= 'Xsd';
        }

        return $name;
    }
}
