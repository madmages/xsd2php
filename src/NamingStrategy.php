<?php

namespace Madmages\Xsd\XsdToPhp;

use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItem;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem;
use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;

interface NamingStrategy
{
    public function getTypeName(Type $type): string;

    public function getAnonymousTypeName(Type $type, string $parent_name): string;

    public function getItemName(Item $item): string;

    /**
     * @param ElementItem|AttributeItem $item $item
     * @return string
     */
    public function getPropertyName($item): string;
}