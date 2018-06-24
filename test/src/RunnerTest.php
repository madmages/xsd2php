<?php

use GoetasWebservices\Xsd\XsdToPhpRuntime\Jms\Handler\BaseTypesHandler;
use GoetasWebservices\Xsd\XsdToPhpRuntime\Jms\Handler\XmlSchemaDateHandler;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\SerializerBuilder;
use Madmages\Xsd\XsdToPhp\App;
use Madmages\Xsd\XsdToPhp\Config;
use Madmages\Xsd\XsdToPhp\NamingStrategy;
use PHPUnit\Framework\TestCase;

class RunnerTest extends TestCase
{
    private const XSD_DIR = 'xsd';
    private const XML_DIR = 'xml';
    private const TMP_DIR = __DIR__ . '/../../tmp';
    private const TMP_JMS = self::TMP_DIR . '/jms';
    private const TMP_PHP = self::TMP_DIR . '/php';
    private const NS_PREFIX = "TMP\\PHP";

    /**
     * @throws \Illuminate\Container\EntryNotFoundException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws Exception
     */
    public function testRun()
    {
        $xsds = glob(self::XSD_DIR . '/*/*.xsd');

        foreach ($xsds as $xsd_path) {
            [$xmls_pattern, $file_name] = $this->getXMLSPattern($xsd_path);
            $config = $this->getConfig($file_name);
            App::run([$xsd_path], $config);

            $xmls = glob($xmls_pattern);
            foreach ($xmls as $xml) {
                $xml_string = file_get_contents($xml);
                $this->isValidXML($xml_string, file_get_contents($xsd_path));

                $simple_xml = new SimpleXMLElement($xml_string);
                $deserialized = $this->getSerializer()->deserialize($xml_string, self::NS_PREFIX . '\\' . ucfirst($file_name), 'xml');

                $this->matchAttributes($simple_xml->attributes('madmages:xsd2php:' . $file_name), $deserialized);
                $this->matchElements($simple_xml, $deserialized);
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
        $path_parts[$last_part_key] = "*.{$xml_part}";

        return [implode('/', $path_parts), implode('', $file_name)];
    }

    private function getConfig(string $ns_part): Config
    {
        return (new Config())->addNamespace(
            'madmages:xsd2php:' . $ns_part,
            self::NS_PREFIX,
            self::TMP_PHP,
            self::TMP_JMS
        );
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
            /** @var NamingStrategy $strategy */
            $strategy = App::getInstance()->get(NamingStrategy::class);
            $getter_method = $strategy->getGetterMethod($attribute_name);

            $deserialized_value = $deserialized->$getter_method();

            switch ($this->getType($deserialized_value)) {
                case DateTime::class:
                    $this->assertTrue($this->compareDateTime($deserialized_value, new DateTime((string)$attribute_value)));
                    break;
                case 'numeric':
                    $this->assertEquals($deserialized_value, (float)$attribute_value);
                    break;
                case 'string':
                    $this->assertEquals($deserialized_value, (string)$attribute_value);
                    break;
                default:
                    throw new \Exception('wrong behaviour');
            }
        }
    }

    /**
     * @param SimpleXMLElement $elements
     * @param $deserialized
     * @throws Exception
     * @throws \Illuminate\Container\EntryNotFoundException
     */
    private function matchElements($elements, $deserialized): void
    {
        $index = 0;
        foreach ($elements as $element_name => $element) {
            /** @var NamingStrategy $strategy */
            $strategy = App::getInstance()->get(NamingStrategy::class);
            $getter_method = $strategy->getGetterMethod($element_name);

            $current_deserialized = $deserialized->$getter_method();

            $this->matchAttributes(
                $element->attributes(),
                is_array($current_deserialized) ? $current_deserialized[$index] : $current_deserialized
            );

            $this->matchElements($element->children(), $current_deserialized);

            is_array($current_deserialized) && $index++;
        }
    }
}