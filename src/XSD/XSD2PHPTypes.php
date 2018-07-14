<?php

namespace Madmages\Xsd\XsdToPhp\XSD;

use Madmages\Xsd\XsdToPhp\Types;

final class XSD2PHPTypes
{
    public const TYPES = [
        XMLSchema::NAMESPACE => [
            XMLSchema::TYPE_BOOLEAN            => Types::BOOL,
            XMLSchema::TYPE_GYEARMONTH         => Types::INT,
            XMLSchema::TYPE_GMONTHDAY          => Types::INT,
            XMLSchema::TYPE_GMONTH             => Types::INT,
            XMLSchema::TYPE_GYEAR              => Types::INT,
            XMLSchema::TYPE_INTEGER            => Types::INT,
            XMLSchema::TYPE_INT                => Types::INT,
            XMLSchema::TYPE_UNSIGNEDINT        => Types::INT,
            XMLSchema::TYPE_NEGATIVEINTEGER    => Types::INT,
            XMLSchema::TYPE_POSITIVEINTEGER    => Types::INT,
            XMLSchema::TYPE_NONNEGATIVEINTEGER => Types::INT,
            XMLSchema::TYPE_NONPOSITIVEINTEGER => Types::INT,
            XMLSchema::TYPE_FLOAT              => Types::FLOAT,
            XMLSchema::TYPE_DOUBLE             => Types::FLOAT,
            XMLSchema::TYPE_LONG               => Types::FLOAT,
            XMLSchema::TYPE_UNSIGNEDLONG       => Types::FLOAT,
            XMLSchema::TYPE_SHORT              => Types::FLOAT,
            XMLSchema::TYPE_DECIMAL            => Types::FLOAT,
            XMLSchema::TYPE_NMTOKEN            => Types::STRING,
            XMLSchema::TYPE_NMTOKENS           => Types::STRING,
            XMLSchema::TYPE_QNAME              => Types::STRING,
            XMLSchema::TYPE_NCNAME             => Types::STRING,
            XMLSchema::TYPE_STRING             => Types::STRING,
            XMLSchema::TYPE_NORMALIZEDSTRING   => Types::STRING,
            XMLSchema::TYPE_LANGUAGE           => Types::STRING,
            XMLSchema::TYPE_TOKEN              => Types::STRING,
            XMLSchema::TYPE_ANYURI             => Types::STRING,
            XMLSchema::TYPE_BYTE               => Types::STRING,
            XMLSchema::TYPE_ID                 => Types::STRING,
            XMLSchema::TYPE_IDREF              => Types::STRING,
            XMLSchema::TYPE_IDREFS             => Types::STRING,
            XMLSchema::TYPE_NAME               => Types::STRING,
            XMLSchema::TYPE_DURATION           => \DateInterval::class,
            XMLSchema::TYPE_DATETIME           => \DateTime::class,
            XMLSchema::TYPE_DATE               => \DateTime::class,
            XMLSchema::TYPE_ANYTYPE            => Types::MIXED,
            XMLSchema::TYPE_ANYSIMPLETYPE      => Types::MIXED,
        ]
    ];
}