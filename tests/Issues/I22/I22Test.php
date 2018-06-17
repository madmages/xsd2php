<?php

namespace Madmages\Xsd\XsdToPhp\Tests\Issues\I22;

use GoetasWebservices\XML\XSDReader\SchemaReader;
use Madmages\Xsd\XsdToPhp\Components\Naming\ShortNamingStrategy;
use Madmages\Xsd\XsdToPhp\Jms\YamlConverter;

class I22Test extends \PHPUnit_Framework_TestCase
{

    public function testNamespace()
    {
        $reader = new SchemaReader();
        $schema = $reader->readFile(__DIR__ . '/data.xsd');

        $jmsConv = new YamlConverter(new ShortNamingStrategy());
        $jmsConv->addNamespace('http://www.example.com', 'XmlListTest');
        $jmsConv->addNamespace('http://www.example2.com', 'XmlListTest');

        $phpClasses = $jmsConv->convert([$schema]);
        $complexType = $phpClasses['XmlListTest\ComplexType1Type']['XmlListTest\ComplexType1Type'];


        $nestedElement = $complexType['properties']['elementList']['xml_list'];
        self::assertEquals('http://www.example2.com', $nestedElement['namespace']);
    }
}
