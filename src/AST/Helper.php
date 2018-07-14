<?php

namespace Madmages\Xsd\XsdToPhp\AST;


use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type as RType;
use Madmages\Xsd\XsdToPhp\Contract\NamingStrategy;
use Madmages\Xsd\XsdToPhp\XSD\XMLSchema;
use RuntimeException;

class Helper
{
    private static $alias_cache = [];
    private static $type_aliases = [];
    public static $naming_strategy;
    public static $base_schemas = [
        XMLSchema::NAMESPACE,
        XMLSchema::NAMESPACE_OLD
    ];

    public static $namespaces = [
        XMLSchema::NAMESPACE     => '',
        XMLSchema::NAMESPACE_OLD => ''
    ];

    public static function getTypeAlias(RType $type): ?SimplePHPType
    {
        $schema_target_namespace = $type->getSchema()->getTargetNamespace();
        $type_name = $type->getName();

        $alias_cache_key = $schema_target_namespace . '|' . $type_name;
        if (array_key_exists($alias_cache_key, self::$alias_cache)) {
            return self::$alias_cache[$alias_cache_key];
        }

        if (isset(self::$type_aliases[$schema_target_namespace][$type_name])) {
            $simple_type = call_user_func(self::$type_aliases[$schema_target_namespace][$type_name], $type);
            self::$alias_cache[$alias_cache_key] = new SimplePHPType($simple_type);
        } else {
            self::$alias_cache[$alias_cache_key] = null;
        }

        return self::$alias_cache[$alias_cache_key];
    }

    public static function addTypeAlias(string $xsd_namespace, string $xsd_type, callable $handler): void
    {
        self::$type_aliases[$xsd_namespace][$xsd_type] = $handler;
    }

    /**
     * @param RType $type
     * @param Item|null $parent
     * @param array|null $parent_namespace
     * @return array
     * @throws \RuntimeException
     */
    public static function resolveTypeNames(RType $type, ?Item $parent, ?array $parent_namespace = []): array
    {
        $schema = $type->getSchema();

        if ($alias = self::getTypeAlias($type)) {
            if (($slash_position = strrpos($alias, '\\')) !== false) {
                return [
                    substr($alias, $slash_position + 1),
                    substr($alias, 0, $slash_position)
                ];
            }

            return [
                $alias,
                null
            ];
        }


        if (!array_key_exists($schema->getTargetNamespace(), self::$namespaces)) {
            throw new RuntimeException(sprintf("Can't find a PHP namespace to '%s' namespace", $schema->getTargetNamespace()));
        }

        $namespace = self::$namespaces[$schema->getTargetNamespace()];
        if ($type->getName() !== null) {
            $type_name = self::getNamingStrategy()->getTypeName($type->getName());
        } else {
            if ($parent === null) {
                throw new RuntimeException('unexpected');
            }

            $type_name = self::getNamingStrategy()->getAnonymousTypeName($parent->getName());
        }

        return [$type_name, $namespace];
    }

    public static function getNamingStrategy(): NamingStrategy
    {
        return self::$naming_strategy;
    }

    public static function getPHPNamespace(SchemaItem $element): string
    {
        return self::$namespaces[$element->getSchema()->getTargetNamespace()];
    }

    public static function isInBaseSchema(Schema $schema)
    {
        return in_array($schema->getTargetNamespace(), self::$base_schemas, true);
    }
}
