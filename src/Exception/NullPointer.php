<?php

namespace Madmages\Xsd\XsdToPhp\Exception;

class NullPointer extends Exception
{
    public function __construct($message = null)
    {
        if ($message === null) {
            $message = 'NULL occur';
        }

        parent::__construct($message);
    }
}