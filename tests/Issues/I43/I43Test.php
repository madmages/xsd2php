<?php

namespace Madmages\Xsd\XsdToPhp\Tests\Issues\I40;

use GoetasWebservices\XML\XSDReader\SchemaReader;
use Madmages\Xsd\XsdToPhp\Tests\Generator;

class I43Test extends \PHPUnit_Framework_TestCase
{
    /**
     * @group long
     */
    public function testOpcGeneration()
    {

        $nss = [
            "http://schemas.openxmlformats.org/package/2006/metadata/core-properties" => "Iag/ECMA376/Package/Model/CoreProperties/",
            "http://purl.org/dc/elements/1.1/"                                        => "Iag/ECMA376/Package/Model/CoreProperties/DcElements/",
            "http://purl.org/dc/terms/"                                               => "Iag/ECMA376/Package/Model/CoreProperties/DcTerms/",
            "http://purl.org/dc/dcmitype/"                                            => "Iag/ECMA376/Package/Model/CoreProperties/DcMiType/",
        ];

        $reader = new SchemaReader();
        $reader->addKnownSchemaLocation('http://dublincore.org/schemas/xmls/qdc/2003/04/02/dc.xsd', __DIR__ . '/opc/dc.xsd');
        $reader->addKnownSchemaLocation('http://dublincore.org/schemas/xmls/qdc/2003/04/02/dcterms.xsd', __DIR__ . '/opc/dcterms.xsd');
        $reader->addKnownSchemaLocation('http://dublincore.org/schemas/xmls/qdc/2003/04/02/dcterms.xsd', __DIR__ . '/opc/dcterms.xsd');
        $reader->addKnownSchemaLocation('http://dublincore.org/schemas/xmls/qdc/2003/04/02/dcmitype.xsd', __DIR__ . '/opc/dcmitype.xsd');

        $schema = $reader->readFile(__DIR__ . '/opc/opc-coreProperties.xsd');

        $generator = new Generator($nss);

        list($phpClasses, $yamlItems) = $generator->getData([$schema]);

        $this->assertEquals(count($phpClasses), count($yamlItems));
        $this->assertGreaterThan(0, count($phpClasses));
    }
}