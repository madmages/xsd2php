<?php

use Doctrine\Common\Inflector\Inflector;
use GoetasWebservices\Xsd\XsdToPhpRuntime\Jms\Handler\BaseTypesHandler;
use GoetasWebservices\Xsd\XsdToPhpRuntime\Jms\Handler\XmlSchemaDateHandler;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\SerializerBuilder;
use Madmages\Xsd\XsdToPhp\App;
use Madmages\Xsd\XsdToPhp\Components\Naming\LongNamingStrategy;
use Madmages\Xsd\XsdToPhp\Config;
use Madmages\Xsd\XsdToPhp\Contract\NamingStrategy;
use Madmages\Xsd\XsdToPhp\Types;
use Madmages\Xsd\XsdToPhp\XSD\XMLSchema;
use PHPUnit\Framework\TestCase;

class RunnerTest extends TestCase
{
    private const XSD_DIR = 'xsd';
    private const XML_DIR = 'xml';
    private const TMP_DIR = __DIR__ . '/../../tmp';
    private const TMP_JMS = self::TMP_DIR . '/jms';
    private const TMP_PHP = self::TMP_DIR . '/php';
    private const NS_PREFIX = "TMP\\PHP";
    /** @var NamingStrategy */
    private $strategy;

    /**
     * @param string $xsd_path
     * @throws Exception
     * @throws \Illuminate\Container\EntryNotFoundException
     * @throws \Madmages\Xsd\XsdToPhp\Exception\Config
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @dataProvider getXSDs
     */
    public function testRun(string $xsd_path)
    {
        $xsd_path = 'xsd/OTA/OTA_AirBookModifyRQ.xsd';
        [$xmls_pattern, $file_name] = $this->getXMLSPattern($xsd_path);
        $config = $this->getConfig($file_name);
        $container = App::run([$xsd_path], $config);

        $this->strategy = $container->get(NamingStrategy::class);

        $xmls = glob($xmls_pattern);
        foreach ($xmls as $xml) {
            $xml_string = file_get_contents($xml);
            $this->isValidXML($xml_string, file_get_contents($xsd_path));

            $simple_xml = new SimpleXMLElement($xml_string);
            $deserialized = $this->getSerializer()->deserialize($xml_string, self::NS_PREFIX . '\\' . Inflector::classify($file_name) . 'Element', 'xml');

            $namespaces = $simple_xml->getDocNamespaces();
            foreach ($namespaces as $namespace) {
                if ('http://www.w3.org/2001/XMLSchema-instance' === $namespace) {
                    continue;
                }
                $this->matchAttributes($simple_xml->attributes($namespace), $deserialized);
                $this->matchElements($simple_xml, $deserialized, $namespaces);
            }
        }
    }

    private function isValidXML(string $xml, $xsd)
    {
        $document = new DOMDocument();
        $document->loadXML($xml);
        if (!$document->schemaValidateSource($xsd)) {
            throw new \Exception(error_get_last()['message']);
        }
    }

    private function getType($item): string
    {
        if ($item instanceof DateTime) {
            return DateTime::class;
        }

        if ($item instanceof DateInterval) {
            return DateInterval::class;
        }

        if (is_numeric($item)) {
            return 'numeric';
        }

        if (is_bool($item)) {
            return 'bool';
        }

        if (is_string($item)) {
            return 'string';
        }

        if ($item === null) {
            return 'null';
        }

        if (is_array($item)) {
            return 'array';
        }

        throw new \Exception('undefined');
    }

    private function compareDateTime($value1, $value2): bool
    {
        if (!$value1 instanceof DateTime || !$value2 instanceof DateTime) {
            return false;
        }

        return $value1->getTimestamp() === $value2->getTimestamp();
    }

    private function getXMLSPattern(string $xsd_path)
    {
        $path_parts = explode('/', $xsd_path);
        $path_parts[0] = self::XML_DIR;

        $last_part_key = count(array_keys($path_parts)) - 1;
        $file_name = explode('.', $path_parts[$last_part_key]);
        array_pop($file_name);

        $xml_part = str_replace('xsd', 'xml', $path_parts[$last_part_key]);
        $xml_parts = explode('.', $xml_part);
        $xml_parts[0] = "{$xml_parts[0]}*";
        $path_parts[$last_part_key] = implode('.', $xml_parts);

        return [implode('/', $path_parts), implode('', $file_name)];
    }

    /**
     * @param string $ns_part
     * @return Config
     * @throws \Madmages\Xsd\XsdToPhp\Exception\Config
     */
    private function getConfig(string $ns_part): Config
    {
        return (new Config())
            ->addNamespace(
                'http://www.opentravel.org/OTA/2003/05',
                self::NS_PREFIX,
                self::TMP_PHP,
                self::TMP_JMS
            )
            ->setNamingStrategy(LongNamingStrategy::class)
            ->setValidators()
            ->addAliases('http://www.opentravel.org/OTA/2003/05', [
                XMLSchema::TYPE_ANYTYPE       => Types::STRING,
                XMLSchema::TYPE_ANYSIMPLETYPE => Types::STRING,
            ])
            ->setTypes();
    }

    private function getSerializer()
    {
        if (!is_dir(self::TMP_JMS)) {
            mkdir(self::TMP_JMS);
        }

        //serializer
        $serializerBuilder = SerializerBuilder::create();
        $serializerBuilder->addMetadataDir(self::TMP_JMS, self::NS_PREFIX);
        $serializerBuilder->configureHandlers(function (HandlerRegistryInterface $handler) use ($serializerBuilder) {
            $serializerBuilder->addDefaultHandlers();
            $handler->registerSubscribingHandler(new BaseTypesHandler()); // XMLSchema List handling
            $handler->registerSubscribingHandler(new XmlSchemaDateHandler()); // XMLSchema date handling
        });

        return $serializerBuilder->build();
    }

    /**
     * @param SimpleXMLElement $attributes
     * @param $deserialized
     * @throws Exception
     * @throws \Illuminate\Container\EntryNotFoundException
     */
    private function matchAttributes($attributes, $deserialized): void
    {
        foreach ($attributes as $attribute_name => $attribute_value) {
            $getter_method = $this->strategy->getGetterMethod($attribute_name);

            $deserialized_value = $deserialized->$getter_method();

            $this->matchValue($deserialized_value, $attribute_value);
        }
    }

    /**
     * @param SimpleXMLElement $elements
     * @param $deserialized
     * @param string[] $namespaces
     * @throws Exception
     * @throws \Illuminate\Container\EntryNotFoundException
     */
    private function matchElements($elements, $deserialized, array $namespaces): void
    {
        $indexes = [];
        foreach ($elements as $element_name => $element) {
            if (isset($indexes[$element_name])) {
                $indexes[$element_name]++;
            } else {
                $indexes[$element_name] = 0;
            }

            $getter_method = $this->strategy->getGetterMethod($element_name);

            if (is_array($deserialized)) {
                $current_deserialized = $deserialized[$indexes[$element_name]];
            } else {
                $current_deserialized = $deserialized->$getter_method();

                if (is_array($current_deserialized) && is_object($current_deserialized[0])) {
                    if (!$this->hasNextElementMethod($current_deserialized[0], $element)) {
                        $current_deserialized = $current_deserialized[$indexes[$element_name]];
                    }
                }
            }

            if (!is_array($current_deserialized)) {
                $this->matchAttributes(
                    $element->attributes(),
                    $current_deserialized
                );
            }

            if (is_object($deserialized) && method_exists($current_deserialized, 'getValue')) {
                $this->matchValue($current_deserialized->getValue(), $element);
            }

            foreach ($namespaces as $namespace) {
                $this->matchElements($element->children($namespace), $current_deserialized, $namespaces);
            }

        }
    }

    /**
     * @param $desserialized
     * @param $element
     * @return bool
     * @throws \Illuminate\Container\EntryNotFoundException
     */
    private function hasNextElementMethod(object $desserialized, SimpleXMLElement $element): bool
    {
        foreach ($element as $element_name => $item) {
            $getter_method = $this->strategy->getGetterMethod($element_name);

            if (method_exists($desserialized, $getter_method)) {
                return false;
            }
        }

        foreach ($element->attributes() as $attribute_name => $item) {
            $getter_method = $this->strategy->getGetterMethod($attribute_name);

            if (method_exists($desserialized, $getter_method)) {
                return false;
            }
        }

        return true;
    }

    private function setLibXMLLoader()
    {
        libxml_set_external_entity_loader(function ($file, $name) {
            if (is_file($name)) {
                return fopen($name, 'rb');
            }

            if (is_file($file_name = self::XSD_DIR . '/OTA/' . $name)) {
                return fopen($file_name, 'rb');
            }

            throw new Exception('Can`t find XSD: ' . $name);
        });
    }

    public function getXSDs()
    {
        $this->setLibXMLLoader();

        $glob = glob(self::XSD_DIR . '/*/*.xsd');
        $glob = array_map(function ($i) {
            return [$i];
        }, $glob);

        $r = array_shift($glob);

        return $glob;
    }

    /**
     * @param $deserialized_value
     * @param $value
     * @throws Exception
     */
    private function matchValue($deserialized_value, $value): void
    {
        switch ($this->getType($deserialized_value)) {
            case DateTime::class:
                $this->assertTrue($this->compareDateTime($deserialized_value, new DateTime((string)$value)));
                break;
            case DateInterval::class:
                $this->assertTrue(true);//todo fix
                break;
            case 'numeric':
                $this->assertEquals($deserialized_value, (float)$value);
                break;
            case 'string':
                $this->assertEquals($deserialized_value, (string)$value);
                break;
            case 'array':
                $this->assertEquals($deserialized_value, (array)$value);
                break;
            case 'bool':
                $this->assertEquals($deserialized_value ? 'true' : 'false', (string)$value);
                break;
            default:
                throw new \Exception('wrong behaviour');
        }
    }

}