<?php

namespace Madmages\Xsd\XsdToPhp;

interface NamingStrategy
{
    public function getTypeName(string $type): string;

    public function getAnonymousTypeName(string $type, string $parent_name): string;

    public function getItemName(string $item): string;

    public function getPropertyName(string $item): string;

    public function getSetterMethod(string $item): string;

    public function getGetterMethod(string $item): string;
}