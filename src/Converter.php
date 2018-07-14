<?php

namespace Madmages\Xsd\XsdToPhp;

use GoetasWebservices\XML\XSDReader\Schema\Schema;
use Madmages\Xsd\XsdToPhp\AST\Helper;
use Madmages\Xsd\XsdToPhp\Contract\NamingStrategy;
use Madmages\Xsd\XsdToPhp\Exception\Exception;
use Madmages\Xsd\XsdToPhp\Php\ClassData;
use Madmages\Xsd\XsdToPhp\XSD\XSD2PHPTypes;

class Converter
{
    private $visited = [];

    /**
     * @param array $schemas
     * @return array
     * @throws \Madmages\Xsd\XsdToPhp\Exception\NullPointer
     * @throws \RuntimeException
     * @throws Exception
     */
    public function process(array $schemas): array
    {
        $classes = [];
        foreach ($schemas as $schema) {
            $classes += $this->navigate($schema);
        }

        return $classes;
    }

    /**
     * @param Schema $schema
     * @return ClassData[]|null
     * @throws \Madmages\Xsd\XsdToPhp\Exception\NullPointer
     * @throws \RuntimeException
     * @throws Exception
     */
    private function navigate(Schema $schema): ?array
    {
        if (isset($this->visited[$schema_id = spl_object_hash($schema)])) {
            return null;
        }
        $this->visited[$schema_id] = true;

        foreach ($schema->getTypes() as $type) {
            AST\Type::handle($type);
        }

        foreach ($schema->getElements() as $element) {
            AST\Element::handle($element, true);
        }

        foreach ($schema->getSchemas() as $child_schema) {
            if (!Helper::isInBaseSchema($child_schema)) {
                $this->navigate($child_schema);
            }
        }

        return ChunkClass::all();
    }

    public function __construct(Config $config, NamingStrategy $naming_strategy)
    {
        Helper::$namespaces = array_replace(Helper::$namespaces, $config->getNamespaces());
        Helper::$naming_strategy = $naming_strategy;

        /** @var string[][] $default_types */
        $default_types = array_merge_recursive(XSD2PHPTypes::TYPES, $config->getAliases());

        foreach ($default_types as $namespace => $types) {
            foreach ($types as $xsd_type => $php_type) {
                Helper::addTypeAlias($namespace, $xsd_type, function () use ($php_type) {
                    return $php_type;
                });
            }
        }
    }
}
