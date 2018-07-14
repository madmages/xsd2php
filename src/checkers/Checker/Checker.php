<?php

namespace Madmages\Xsd\XsdToPhp\Php\Checker;


interface Checker
{
    public const MAP = [
        'length'         => Length::class,
        'minLength'      => MinLength::class,
        'maxLength'      => MaxLength::class,
        'enumeration'    => Enumiration::class,
        'pattern'        => Pattern::class,
        'minExclusive'   => MinExclusive::class,
        'minInclusive'   => MinInclusive::class,
        'maxExclusive'   => MaxExclusive::class,
        'maxInclusive'   => MaxInclusive::class,
        'totalDigits'    => TotalDigits::class,
        'fractionDigits' => FractionDigits::class,
        'whiteSpace'     => NullChecker::class,
    ];

    public function render(): string;
}