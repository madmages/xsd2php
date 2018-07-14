<?php

namespace Madmages\Xsd\XsdToPhp\Contract;

interface NamingStrategy
{
    public function getTypeName(string $type_name): string;

    public function getElementName(string $element_name): string;

    public function getAnonymousTypeName(string $parent_name): string;

    public function getItemName(string $item_name): string;

    public function getPropertyName(string $property_name): string;

    public function getSetterMethod(string $property_name): string;

    public function getGetterMethod(string $property_name): string;
}