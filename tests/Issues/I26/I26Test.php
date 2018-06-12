<?php

namespace Madmages\Xsd\XsdToPhp\Tests\Issues\I26;

use GoetasWebservices\XML\XSDReader\SchemaReader;
use Madmages\Xsd\XsdToPhp\Jms\YamlConverter;
use Madmages\Xsd\XsdToPhp\Naming\ShortNamingStrategy;

class I26Test extends \PHPUnit_Framework_TestCase
{
    public function testSkipWhenEmptyOnOptionalAndRequiredLists()
    {
        $reader = new SchemaReader();
        $schema = $reader->readFile(__DIR__ . '/data.xsd');

        $yamlConverter = new YamlConverter(new ShortNamingStrategy());
        $yamlConverter->addNamespace('', 'NestedArrayTest');
        $phpClasses = $yamlConverter->convert([$schema]);

        $mainElement = $phpClasses['NestedArrayTest\MainElementType']['NestedArrayTest\MainElementType'];
        $required = $mainElement['properties']['requiredElementList'];
        $optional = $mainElement['properties']['optionalElementList'];

        self::assertEquals(false, $required['xml_list']['skip_when_empty']);
        self::assertEquals(true, $optional['xml_list']['skip_when_empty']);
    }

}