<?php

namespace Madmages\Xsd\XsdToPhp\Handler;

use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItem;
use Madmages\Xsd\XsdToPhp\ChunkClass;
use Madmages\Xsd\XsdToPhp\ChunkClassProperty;
use Madmages\Xsd\XsdToPhp\ChunkProperty;
use Madmages\Xsd\XsdToPhp\Config;
use Madmages\Xsd\XsdToPhp\Contract\NamingStrategy;
use Madmages\Xsd\XsdToPhp\Types;

class Jms
{
    /** @var Config */
    private $config;

    public function __construct(Config $config, NamingStrategy $naming_strategy)
    {
        $this->config = $config;
        $this->naming_strategy = $naming_strategy;
    }

    /**
     * @param ChunkClass $chunk_class
     * @return array
     */
    public function handle(ChunkClass $chunk_class): array
    {
        foreach ($this->getProperties($chunk_class) as $property) {
            [$property_name, $property_data] = $this->handleProperty($property);
            $properties[$property_name] = $property_data;
        }

        $result_data = [
            'xml_root_namespace' => $chunk_class->getXsdNamespace(),
            'xml_root_name'      => $chunk_class->getXsdNamespace(),
            'access_type'        => 'public_method ',
        ];

        if (!empty($properties)) {
            $result_data['properties'] = $properties;
        }

        return [$chunk_class->getFullClassName() => $result_data];
    }

    /**
     * @param ChunkClassProperty|ChunkProperty $property
     * @return array
     */
    private function handleProperty($property): array
    {
        $type = $this->getType($property->getType()->getTypeName());
        $type = Types::format($type);
        $property_name = $property instanceof ChunkClassProperty ? Php::ELEMENT_CONTENT_PROPERTY : $property->getName();
        if ($property->isMultiple()) {
            $type = "array<{$type}>";
        }

        $schema = $property->getNode()->getSchema();
        $result = [
            'serialized_name' => $property->getNode()->getName(),
            'accessor'        => [
                'getter' => $this->getGetterMethod($property_name),
                'setter' => $this->getSetterMethod($property_name)
            ],
            'type'            => $type,
            'xml_attribute'   => $property->getNode() instanceof AttributeItem,
            'xml_value'       => $property instanceof ChunkClassProperty,
        ];

        if ($property->isMultiple()) {
            $result['xml_list'] = [
                'inline'     => true,
                'entry_name' => $property->getNode()->getName(),
                'namespace'  => $schema->getTargetNamespace(),
            ];
        }

        if (
            (empty($result['xml_attribute']) && $schema->getElementsQualification())
            || ($result['xml_attribute'] && $schema->getAttributesQualification())
        ) {
            $result['xml_element']['namespace'] = $schema->getTargetNamespace();
        }

        return [$property_name, $result];
    }

    private function getGetterMethod(string $name): string
    {
        return $this->naming_strategy->getGetterMethod($this->naming_strategy->getPropertyName($name));
    }

    private function getSetterMethod(string $name): string
    {
        return $this->naming_strategy->getSetterMethod($this->naming_strategy->getPropertyName($name));
    }

    private function getProperties(?ChunkClass $chunk_class)
    {
        if (!$chunk_class) {
            return [];
        }

        $result = $chunk_class->getProperties();

        if ($chunk_class instanceof ChunkClassProperty) {
            $result[] = $chunk_class;
        }

        if ($parent = $chunk_class->getParent()) {
            $result = array_merge($result, $this->getProperties($parent));
        }

        return $result;
    }

    private function getType(string $type_name): string
    {
        if ($type_name === \DateTime::class) {
            return 'GoetasWebservices\Xsd\XsdToPhp\XMLSchema\DateTime';
        }

        return $type_name;
    }
}
