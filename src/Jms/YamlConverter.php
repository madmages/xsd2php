<?php

namespace Madmages\Xsd\XsdToPhp\Jms;

use GoetasWebservices\XML\XSDReader\Schema\Attribute\AbstractAttributeItem;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeContainer;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItem;
use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementContainer;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementRef;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementSingle;
use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;
use GoetasWebservices\XML\XSDReader\Schema\Type\BaseComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;
use Madmages\Xsd\XsdToPhp\AbstractConverter;
use Madmages\Xsd\XsdToPhp\Config;
use Madmages\Xsd\XsdToPhp\Exception\Config as ConfigException;
use Madmages\Xsd\XsdToPhp\Exception\NullPointer;
use Madmages\Xsd\XsdToPhp\NamingStrategy;
use Madmages\Xsd\XsdToPhp\Php\Structure\PHPClass;
use Madmages\Xsd\XsdToPhp\XMLSchema;

class YamlConverter extends AbstractConverter
{
    private $classes = [];

    public function __construct(NamingStrategy $naming_strategy, Config $config)
    {
        parent::__construct($naming_strategy, $config);

        $this->addAliasMap(XMLSchema::NAMESPACE, XMLSchema::TYPE_DATETIME, function () {
            return "GoetasWebservices\Xsd\XsdToPhp\XMLSchema\DateTime";//Type from goetas-webservices/xsd2php-runtime
        });

        $this->addAliasMap(XMLSchema::NAMESPACE, XMLSchema::TYPE_TIME, function () {
            return "GoetasWebservices\Xsd\XsdToPhp\XMLSchema\Time";//Type from goetas-webservices/xsd2php-runtime
        });

        $this->addAliasMap(XMLSchema::NAMESPACE, XMLSchema::TYPE_DATE, function () {
            return "GoetasWebservices\Xsd\XsdToPhp\XMLSchema\Date";//Type from goetas-webservices/xsd2php-runtime
        });
    }

    /**
     * @param array $schemas
     * @return array
     * @throws NullPointer
     * @throws ConfigException
     */
    public function convert(array $schemas): array
    {
        $visited = [];
        $this->classes = [];

        foreach ($schemas as $schema) {
            $this->navigate($schema, $visited);
        }

        return $this->getTypes();
    }

    /**
     * @param Schema $schema
     * @param array $visited
     * @throws NullPointer
     * @throws ConfigException
     */
    private function navigate(Schema $schema, array &$visited): void
    {
        if (isset($visited[spl_object_hash($schema)])) {
            return;
        }

        $visited[spl_object_hash($schema)] = true;

        foreach ($schema->getTypes() as $type) {
            $this->visitType($type);
        }

        foreach ($schema->getElements() as $element) {
            $this->visitElementDef($schema, $element);
        }

        foreach ($schema->getSchemas() as $schildSchema) {
            if (!in_array($schildSchema->getTargetNamespace(), $this->base_schemas, true)) {
                $this->navigate($schildSchema, $visited);
            }
        }
    }

    /**
     * @param Type $type
     * @param bool $force
     * @return array
     * @throws NullPointer
     * @throws ConfigException
     */
    public function &visitType(Type $type, bool $force = false): array
    {
        $skip = in_array($type->getSchema()->getTargetNamespace(), $this->base_schemas, true);

        if (!isset($this->classes[spl_object_hash($type)])) {
            $this->classes[spl_object_hash($type)]['skip'] = $skip;

            if ($alias = $this->getTypeAlias($type)) {
                $class = [];
                $class[$alias] = [];

                $this->classes[spl_object_hash($type)]['class'] = &$class;
                $this->classes[spl_object_hash($type)]['skip'] = true;
                return $class;
            }

            $className = $this->findPHPName($type);

            $class = [];
            $data = [];

            $class[$className] = &$data;

            $this->classes[spl_object_hash($type)]['class'] = &$class;

            $this->visitTypeBase($class, $data, $type, $type->getName());

            if ($type instanceof SimpleType) {
                $this->classes[spl_object_hash($type)]['skip'] = true;
                return $class;
            }

            if (!$force && ($this->isArrayType($type) || $this->isArrayNestedElement($type))) {
                $this->classes[spl_object_hash($type)]['skip'] = true;
                return $class;
            }
        } else {
            if ($force && !($type instanceof SimpleType) && !$this->getTypeAlias($type)) {
                $this->classes[spl_object_hash($type)]['skip'] = $skip;
            }
        }

        return $this->classes[spl_object_hash($type)]['class'];
    }

    /**
     * @param Type $type
     * @return mixed|string
     */
    private function findPHPName(Type $type)
    {
        $schema = $type->getSchema();

        if ($alias = $this->getTypeAlias($type, $schema)) {
            return $alias;
        }

        $namespace = $this->findPHPNamespace($type);
        $name = $this->getNamingStrategy()->getTypeName($type->getName());

        return $namespace . PHPClass::NS_SLASH . $name;
    }

    /**
     * @param SchemaItem $item
     * @return mixed
     */
    private function findPHPNamespace(SchemaItem $item)
    {
        $schema = $item->getSchema();

        if (!isset($this->namespaces[$schema->getTargetNamespace()])) {
            throw new \RuntimeException(sprintf('Can\'t find a PHP namespace to `%s` namespace', $schema->getTargetNamespace()));
        }

        return $this->namespaces[$schema->getTargetNamespace()];
    }

    /**
     * @param $class
     * @param $data
     * @param Type $type
     * @param $name
     * @throws NullPointer
     * @throws ConfigException
     */
    private function visitTypeBase(&$class, &$data, Type $type, $name)
    {
        if ($type instanceof BaseComplexType) {
            $this->visitBaseComplexType($class, $data, $type, $name);
        }

        if ($type instanceof ComplexType) {
            $this->visitComplexType($class, $data, $type);
        }

        if ($type instanceof SimpleType) {
            $this->visitSimpleType($class, $data, $type, $name);
        }
    }

    /**
     * @param $class
     * @param $data
     * @param BaseComplexType $type
     * @param $name
     * @throws NullPointer
     * @throws ConfigException
     */
    private function visitBaseComplexType(&$class, &$data, BaseComplexType $type, $name)
    {
        $parent = $type->getParent();
        if ($parent) {
            $parentType = $parent->getBase();
            if ($parentType instanceof Type) {
                $this->handleClassExtension($class, $data, $parentType, $name);
            }
        }

        $schema = $type->getSchema();
        if (!isset($data['properties'])) {
            $data['properties'] = [];
        }
        foreach ($this->flattAttributes($type) as $attr) {
            $data['properties'][$this->getNamingStrategy()->getPropertyName($attr->getName())] = $this->visitAttribute($class, $schema, $attr);
        }
    }

    /**
     * @param $class
     * @param $data
     * @param Type $type
     * @param $parentName
     * @throws NullPointer
     * @throws ConfigException
     */
    private function handleClassExtension(&$class, &$data, Type $type, $parentName)
    {
        if ($alias = $this->getTypeAlias($type)) {
            $property = [
                'expose'      => true,
                'xml_value'   => true,
                'access_type' => 'public_method',
                'type'        => $alias,
                'accessor'    => [
                    'getter' => 'value',
                    'setter' => 'value',
                ],
            ];

            $data['properties'][PHPClass::VALUE_CLASS] = $property;
        } else {
            $extension = $this->visitType($type, true);

            if (isset($extension['properties'][PHPClass::VALUE_CLASS]) && count($extension['properties']) === 1) {
                $data['properties'][PHPClass::VALUE_CLASS] = $extension['properties'][PHPClass::VALUE_CLASS];
            } else {
                if ($type instanceof SimpleType) {
                    $property = [
                        'expose'      => true,
                        'xml_value'   => true,
                        'access_type' => 'public_method',
                        'accessor'    => [
                            'getter' => 'value',
                            'setter' => 'value'
                        ],
                    ];

                    if ($valueProp = $this->typeHasValue($type, $class, $parentName)) {
                        $property['type'] = $valueProp;
                    } else {
                        $property['type'] = key($extension);
                    }

                    $data['properties'][PHPClass::VALUE_CLASS] = $property;
                }
            }
        }
    }

    /**
     * @param Type $type
     * @param $parent_class
     * @param $name
     * @return bool|mixed
     * @throws NullPointer
     * @throws ConfigException
     */
    private function typeHasValue(?Type $type, $parent_class, $name)
    {
        do {
            if (!$type instanceof SimpleType) {
                return false;
            }

            if ($alias = $this->getTypeAlias($type)) {
                return $alias;
            }

            if ($type->getName()) {
                $parent_class = $this->visitType($type);
            } else {
                $parent_class = $this->visitTypeAnonymous($type, $name, key($parent_class));
            }

            $props = reset($parent_class);
            if (isset($props['properties'][PHPClass::VALUE_CLASS]) && count($props['properties']) === 1) {
                return $props['properties'][PHPClass::VALUE_CLASS]['type'];
            }
        } while (method_exists($type, 'getRestriction') && $type->getRestriction() && $type = $type->getRestriction()->getBase());

        return false;
    }

    /**
     * @param Type $type
     * @param string $parentName
     * @param string $parentClass
     * @return array
     * @throws NullPointer
     * @throws ConfigException
     */
    private function &visitTypeAnonymous(Type $type, $parentName, $parentClass): array
    {
        if (!isset($this->classes[spl_object_hash($type)])) {
            $class = [];
            $data = [];

            $name = $this->getNamingStrategy()->getAnonymousTypeName($type, $parentName);

            $class[$parentClass . PHPClass::NS_SLASH . $name] = &$data;

            $this->visitTypeBase($class, $data, $type, $parentName);
            $this->classes[spl_object_hash($type)]['class'] = &$class;
            if ($type instanceof SimpleType) {
                $this->classes[spl_object_hash($type)]['skip'] = true;
            }
        }

        return $this->classes[spl_object_hash($type)]['class'];
    }

    /**
     * @param AttributeContainer $container
     * @return AttributeItem[]
     */
    private function flattAttributes(AttributeContainer $container): array
    {
        $items = [];
        foreach ($container->getAttributes() as $attr) {
            if ($attr instanceof AttributeContainer) {
                $items = array_merge($items, $this->flattAttributes($attr));
            } else {
                $items[] = $attr;
            }
        }

        return $items;
    }

    /**
     * @param $class
     * @param Schema $schema
     * @param AbstractAttributeItem $attribute
     * @return array
     * @throws ConfigException
     * @throws NullPointer
     */
    private function visitAttribute(&$class, Schema $schema, AbstractAttributeItem $attribute): array
    {
        $property = [
            'expose'          => true,
            'xml_attribute'   => true,
            'access_type'     => 'public_method',
            'serialized_name' => $attribute->getName(),
            'accessor'        => [
                'getter' => $this->getNamingStrategy()->getGetterMethod($this->getNamingStrategy()->getPropertyName($attribute->getName())),
                'setter' => $this->getNamingStrategy()->getSetterMethod($this->getNamingStrategy()->getPropertyName($attribute->getName()))
            ],
        ];

        if (($attribute instanceof Attribute && $attribute->isQualified()) && !$schema->getElementsQualification()) {
            echo 1;
        }

        if ($schema->getElementsQualification() && $attribute->getSchema()->getTargetNamespace()) {
            $property['xml_element']['namespace'] = $attribute->getSchema()->getTargetNamespace();
        }

        if ($alias = $this->getTypeAlias($attribute)) {
            $property['type'] = $alias;

        } else {
            if ($itemOfArray = $this->isArrayType($attribute->getType())) {
                if ($valueProp = $this->typeHasValue($itemOfArray, $class, 'xx')) {
                    $property['type'] = "GoetasWebservices\Xsd\XsdToPhp\Jms\SimpleListOf<" . $valueProp . '>';
                } else {
                    $property['type'] = "GoetasWebservices\Xsd\XsdToPhp\Jms\SimpleListOf<" . $this->findPHPName($itemOfArray) . '>';
                }

                $property['xml_list']['inline'] = false;
                $property['xml_list']['entry_name'] = $itemOfArray->getName();
                if ($schema->getTargetNamespace() && ($schema->getElementsQualification() || ($itemOfArray instanceof Element && $itemOfArray->isQualified()))) {
                    $property['xml_list']['entry_namespace'] = $schema->getTargetNamespace();
                }
            } else {
                $property['type'] = $this->findPHPClass($class, $attribute);
            }
        }
        return $property;
    }

    /**
     * @param $class
     * @param Item $node
     * @return mixed
     * @throws NullPointer
     * @throws ConfigException
     */
    private function findPHPClass(&$class, Item $node)
    {
        $type = $node->getType();
        if ($type === null) {
            throw new NullPointer();
        }

        if ($alias = $this->getTypeAlias($type)) {
            return $alias;
        }

        if ($type->getName() === XMLSchema::TYPE_ANYTYPE) {
            throw new ConfigException(sprintf('Config should present `%s` type handler', XMLSchema::TYPE_ANYTYPE));
        }

        if ($node instanceof ElementRef) {
            $elementRef = $this->visitElementDef($node->getSchema(), $node->getReferencedElement());
            return key($elementRef);
        }

        if ($valueProp = $this->typeHasValue($type, $class, '')) {
            return $valueProp;
        }

        if (!$type->getName()) {
            $visited = $this->visitTypeAnonymous($type, $node->getName(), key($class));
        } else {
            $visited = $this->visitType($type);
        }

        return key($visited);
    }

    /**
     * @param Schema $schema
     * @param ElementDef $element
     * @return mixed
     * @throws NullPointer
     * @throws ConfigException
     */
    public function &visitElementDef(Schema $schema, ElementDef $element)
    {
        if (!isset($this->classes[spl_object_hash($element)])) {
            $namespace = $this->findPHPNamespace($element) . PHPClass::NS_SLASH . $this->getNamingStrategy()->getItemName($element->getName());
            $class = [];
            $data = [];
            $class[$namespace] = &$data;
            $data['xml_root_name'] = $element->getName();

            if ($schema->getTargetNamespace()) {
                $data['xml_root_namespace'] = $schema->getTargetNamespace();

                if (!$schema->getElementsQualification()) {
                    $data['xml_root_name'] = 'ns-' . substr(sha1($data['xml_root_namespace']), 0, 8) . ':' . $data['xml_root_name'];
                }
            }

            $this->classes[spl_object_hash($element)]['class'] = &$class;

            $element_type = $element->getType();
            if ($element_type === null) {
                throw new NullPointer();
            }

            if (!$element_type->getName()) {
                $this->visitTypeBase($class, $data, $element_type, $element->getName());
            } else {
                $this->handleClassExtension($class, $data, $element_type, $element->getName());
            }
        }

        $this->classes[spl_object_hash($element)]['skip'] = in_array($element->getSchema()->getTargetNamespace(), $this->base_schemas, true);
        return $this->classes[spl_object_hash($element)]['class'];
    }

    /**
     * @param $class
     * @param $data
     * @param ComplexType $type
     * @throws ConfigException
     * @throws NullPointer
     */
    private function visitComplexType(&$class, &$data, ComplexType $type)
    {
        $schema = $type->getSchema();
        if (!isset($data['properties'])) {
            $data['properties'] = [];
        }
        foreach ($this->flattElements($type) as $element) {
            $element_name = $this->getNamingStrategy()->getPropertyName($element->getName());
            $data['properties'][$element_name] = $this->visitElement($class, $schema, $element);
        }
    }

    /**
     * @param ElementContainer $container
     * @return ElementItem[]
     */
    private function flattElements(ElementContainer $container): array
    {
        $items = [];
        foreach ($container->getElements() as $element) {
            if ($element instanceof ElementContainer) {
                $items = array_merge($items, $this->flattElements($element));
            } else {
                $items[] = $element;
            }
        }

        return $items;
    }

    /**
     * @param PHPClass|array[][] $class
     * @param Schema $schema
     * @param Element|ElementSingle $element
     * @param bool $arrayize
     * @return array
     * @throws NullPointer
     * @throws ConfigException
     */
    private function visitElement(&$class, Schema $schema, Element $element, $arrayize = true): array
    {
        $property = [
            'expose'          => true,
            'access_type'     => 'public_method',
            'serialized_name' => $element->getName(),
            'accessor'        => [
                'getter' => $this->getNamingStrategy()->getGetterMethod($this->getNamingStrategy()->getPropertyName($element->getName())),
                'setter' => $this->getNamingStrategy()->getSetterMethod($this->getNamingStrategy()->getPropertyName($element->getName())),
            ]
        ];

        if ($schema->getElementsQualification() && $element->getSchema()->getTargetNamespace()) {
            $property['xml_element']['namespace'] = $element->getSchema()->getTargetNamespace();
        }

        $element_type = $element->getType();
        if ($element_type === null) {
            throw new NullPointer();
        }

        if ($arrayize) {
            if ($item_of_array = $this->isArrayNestedElement($element_type)) {
                if (!$element_type->getName()) {
                    if ($element instanceof ElementRef) {
                        $item_class = $this->findPHPClass($class, $element);
                    } else {
                        $item_class = key($class);
                    }

                    $class_type = $this->visitTypeAnonymous($element_type, $element->getName(), $item_class);
                } else {
                    $class_type = $this->visitType($element_type);
                }

                $visited = $this->visitElement($class_type, $schema, $item_of_array, false);

                $property['type'] = 'array<' . $visited['type'] . '>';
                $property['xml_list']['inline'] = false;
                $property['xml_list']['entry_name'] = $item_of_array->getName();
                $property['xml_list']['skip_when_empty'] = ($element->getMin() === 0);

                if ($item_of_array->getSchema()->getTargetNamespace() && $item_of_array->getSchema()->getElementsQualification()) {
                    $property['xml_list']['namespace'] = $item_of_array->getSchema()->getTargetNamespace();
                }

                return $property;
            }

            if ($item_of_array = $this->isArrayType($element_type)) {
                if (!$element_type->getName()) {
                    if ($element instanceof ElementRef) {
                        $item_class = $this->findPHPClass($class, $element);
                    } else {
                        $item_class = key($class);
                    }

                    $visitedType = $this->visitTypeAnonymous($item_of_array, $element->getName(), $item_class);

                    if ($prop = $this->typeHasValue($item_of_array, $class, 'xx')) {
                        $property['type'] = "array<{$prop}>";
                    } else {
                        $property['type'] = 'array<' . key($visitedType) . '>';
                    }
                } else {
                    $this->visitType($item_of_array);
                    $property['type'] = 'array<' . $this->findPHPName($item_of_array) . '>';
                }

                $property['xml_list']['inline'] = false;
                $property['xml_list']['entry_name'] = $item_of_array->getName();
                $property['xml_list']['skip_when_empty'] = ($element->getMin() === 0);

                if ($schema->getTargetNamespace() && ($schema->getElementsQualification() || ($element instanceof Element && $element->isQualified()))) {
                    $property['xml_list']['namespace'] = $schema->getTargetNamespace();
                }
                return $property;
            }

            if ($this->isArrayElement($element)) {
                $property['xml_list']['inline'] = true;
                $property['xml_list']['entry_name'] = $element->getName();
                if ($schema->getTargetNamespace() && ($schema->getElementsQualification() || ($element instanceof Element && $element->isQualified()))) {
                    $property['xml_list']['namespace'] = $schema->getTargetNamespace();
                }

                $property['type'] = 'array<' . $this->findPHPClass($class, $element) . '>';
                return $property;
            }
        }

        $property['type'] = $this->findPHPClass($class, $element);
        return $property;
    }

    /**
     * @param $class
     * @param $data
     * @param SimpleType $type
     * @param $name
     * @throws NullPointer
     * @throws ConfigException
     */
    private function visitSimpleType(&$class, &$data, SimpleType $type, $name)
    {
        if ($restriction = $type->getRestriction()) {
            $parent = $restriction->getBase();
            if ($parent instanceof Type) {
                $this->handleClassExtension($class, $data, $parent, $name);
            }
        } else {
            if ($unions = $type->getUnions()) {
                $this->handleClassExtension($class, $data, $unions[$i = key($unions)], $name . $i);
            }
        }
    }

    /**
     * @return PHPClass[]
     */
    public function getTypes(): array
    {
        uasort($this->classes, function ($a, $b) {
            return strcmp(key($a), key($b));
        });

        $result = [];

        foreach ($this->classes as $definition) {
            $class_name = key($definition['class']);
            if (
                (!isset($definition['skip']) || !$definition['skip'])
                && strpos($class_name, PHPClass::NS_SLASH) !== false
            ) {
                $result[$class_name] = $definition['class'];
            }
        }

        return $result;
    }
}
