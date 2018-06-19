<?php

namespace Madmages\Xsd\XsdToPhp;

use Madmages\Xsd\XsdToPhp\Php\Types;

final class XSD2PHPTypes
{
    public const XML_SCHEMA_NS = 'http://www.w3.org/2001/XMLSchema';

    public const TYPES = [
        self::XML_SCHEMA_NS => [
            'boolean'            => Types::BOOL,
            'gYearMonth'         => Types::INT,
            'gMonthDay'          => Types::INT,
            'gMonth'             => Types::INT,
            'gYear'              => Types::INT,
            'integer'            => Types::INT,
            'int'                => Types::INT,
            'unsignedInt'        => Types::INT,
            'negativeInteger'    => Types::INT,
            'positiveInteger'    => Types::INT,
            'nonNegativeInteger' => Types::INT,
            'nonPositiveInteger' => Types::INT,
            'float'              => Types::FLOAT,
            'double'             => Types::FLOAT,
            'long'               => Types::FLOAT,
            'unsignedLong'       => Types::FLOAT,
            'short'              => Types::FLOAT,
            'decimal'            => Types::FLOAT,
            'NMTOKEN'            => Types::STRING,
            'NMTOKENS'           => Types::STRING,
            'QName'              => Types::STRING,
            'NCName'             => Types::STRING,
            'string'             => Types::STRING,
            'normalizedString'   => Types::STRING,
            'language'           => Types::STRING,
            'token'              => Types::STRING,
            'anyURI'             => Types::STRING,
            'byte'               => Types::STRING,
            'ID'                 => Types::STRING,
            'IDREF'              => Types::STRING,
            'IDREFS'             => Types::STRING,
            'Name'               => Types::STRING,
            'duration'           => \DateInterval::class,
        ]
    ];
}