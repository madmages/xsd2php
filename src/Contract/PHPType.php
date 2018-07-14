<?php

namespace Madmages\Xsd\XsdToPhp\Contract;

interface PHPType
{
    public function getTypeName(): string;

    public function isSimple(): bool;

    public function isNative(): bool;
}