<?php

namespace Madmages\Xsd\XsdToPhp\Tests\JmsSerializer\OTA;

use GoetasWebservices\XML\XSDReader\SchemaReader;
use Madmages\Xsd\XsdToPhp\Jms\YamlConverter;
use Madmages\Xsd\XsdToPhp\Naming\ShortNamingStrategy;
use Madmages\Xsd\XsdToPhp\Php\ClassGenerator;
use Madmages\Xsd\XsdToPhp\Php\PhpConverter;

class AnyTypePHPConversionTest extends \PHPUnit_Framework_TestCase
{

    public function testSimpleAnyTypePHP()
    {
        $xml = '
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="single">
                    <xs:all>
                        <xs:element name="id" type="xs:anyType"/>
                    </xs:all>
                </xs:complexType>
            </xs:schema>';

        $items = $this->getPhpClasses($xml, [
            ['http://www.w3.org/2001/XMLSchema', 'anyType', 'mixed']
        ]);


        $this->assertCount(1, $items);

        $single = $items['Example\SingleType'];

        $this->assertTrue($single->hasMethod('getId'));
        $this->assertTrue($single->hasMethod('setId'));

        $returnTags = $single->getMethod('getId')->getDocBlock()->getTags();

        $this->assertCount(1, $returnTags);
        $this->assertEquals(['mixed'], $returnTags[0]->getTypes());
    }

    /**
     *
     * @param mixed $xml
     * @return \Zend\Code\Generator\ClassGenerator[]
     */
    protected function getPhpClasses($xml, array $types = [])
    {
        $creator = new PhpConverter(new ShortNamingStrategy());
        $creator->addNamespace('', 'Example');

        foreach ($types as $typeData) {
            list($ns, $name, $type) = $typeData;
            $creator->addAliasMapType($ns, $name, $type);
        }

        $generator = new ClassGenerator();
        $reader = new SchemaReader();

        if (!is_array($xml)) {
            $xml = [
                'schema.xsd' => $xml
            ];
        }
        $schemas = [];
        foreach ($xml as $name => $str) {
            $schemas[] = $reader->readString($str, $name);
        }
        $items = $creator->convert($schemas);

        $classes = [];
        foreach ($items as $k => $item) {
            if ($codegen = $generator->generateClass($item)) {
                $classes[$k] = $codegen;
            }
        }
        return $classes;
    }

    public function testSimpleAnyTypeYaml()
    {
        $xml = '
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="single">
                    <xs:all>
                        <xs:element name="id" type="xs:anyType"/>
                    </xs:all>
                </xs:complexType>
            </xs:schema>';

        $items = $this->getYamlFiles($xml, [
            ['http://www.w3.org/2001/XMLSchema', 'anyType', 'My\Custom\MixedTypeHandler']
        ]);


        $this->assertCount(1, $items);

        $single = $items['Example\SingleType'];

        $this->assertEquals([
            'Example\\SingleType' => [
                'properties' => [
                    'id' => [
                        'expose'          => true,
                        'access_type'     => 'public_method',
                        'serialized_name' => 'id',
                        'accessor'        => [
                            'getter' => 'getId',
                            'setter' => 'setId'
                        ],
                        'type'            => 'My\\Custom\\MixedTypeHandler'
                    ]
                ]
            ]
        ], $single);
    }

    /**
     *
     * @param mixed $xml
     * @return array[]
     * @throws \GoetasWebservices\XML\XSDReader\Exception\IOException
     */
    protected function getYamlFiles($xml, array $types = [])
    {
        $creator = new YamlConverter(new ShortNamingStrategy());
        $creator->addNamespace('', 'Example');

        foreach ($types as $typeData) {
            list($ns, $name, $type) = $typeData;
            $creator->addAliasMapType($ns, $name, $type);
        }

        $reader = new SchemaReader();

        if (!is_array($xml)) {
            $xml = [
                'schema.xsd' => $xml
            ];
        }
        $schemas = [];
        foreach ($xml as $name => $str) {
            $schemas[] = $reader->readString($str, $name);
        }
        $items = $creator->convert($schemas);

        return $items;
    }
}
