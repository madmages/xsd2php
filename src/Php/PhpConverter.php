<?php

namespace Madmages\Xsd\XsdToPhp\Php;

use Exception;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeDef;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeSingle;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Group as AttributeGroup;
use GoetasWebservices\XML\XSDReader\Schema\Element\AbstractElementSingle;
use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementRef;
use GoetasWebservices\XML\XSDReader\Schema\Element\Group;
use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\Type\BaseComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;
use Madmages\Xsd\XsdToPhp\AbstractConverter;
use Madmages\Xsd\XsdToPhp\Config;
use Madmages\Xsd\XsdToPhp\Exception\NullPointer;
use Madmages\Xsd\XsdToPhp\NamingStrategy;
use Madmages\Xsd\XsdToPhp\Php\Structure\PHPClass;
use Madmages\Xsd\XsdToPhp\Php\Structure\PHPClassOf;
use Madmages\Xsd\XsdToPhp\Php\Structure\PHPProperty;
use Madmages\Xsd\XsdToPhp\SchemaConverter;
use Madmages\Xsd\XsdToPhp\XMLSchema;
use RuntimeException;

class PhpConverter extends AbstractConverter implements SchemaConverter
{
    /** @var ClassData[] */
    private $class_datas;

    public function __construct(NamingStrategy $naming_strategy, Config $config)
    {
        parent::__construct($naming_strategy, $config);

        $this->addAliasMap(XMLSchema::NAMESPACE, XMLSchema::TYPE_DATETIME, function () {
            return \DateTime::class;
        });
        $this->addAliasMap(XMLSchema::NAMESPACE, XMLSchema::TYPE_TIME, function () {
            return \DateTime::class;
        });
        $this->addAliasMap(XMLSchema::NAMESPACE, XMLSchema::TYPE_DATE, function () {
            return \DateTime::class;
        });
        $this->addAliasMap(XMLSchema::NAMESPACE, XMLSchema::TYPE_ANYSIMPLETYPE, function () {
            return Types::MIXED;
        });
        $this->addAliasMap(XMLSchema::NAMESPACE, XMLSchema::TYPE_ANYTYPE, function () {
            return Types::MIXED;
        });
    }

    /**
     * @param Schema[] $schemas
     * @return PHPClass[]
     * @throws Exception
     */
    public function convert(array $schemas): array
    {
        $visited = [];
        $class_datas = [];
        foreach ($schemas as $schema) {
            $class_datas = $this->navigate($schema, $visited);
        }

        return $this->getTypes($class_datas);
    }

    /**
     * @param Schema $schema
     * @param bool[] $visited
     * @return ClassData[]|null
     * @throws Exception
     */
    private function navigate(Schema $schema, array &$visited): ?array
    {
        if (isset($visited[$schema_id = spl_object_hash($schema)])) {
            return null;
        }
        $visited[$schema_id] = true;

        foreach ($schema->getTypes() as $type) {
            $this->visitType($type);
        }

        foreach ($schema->getElements() as $element) {
            $this->getClassByElement($element);
        }

        foreach ($schema->getSchemas() as $child_schema) {
            if (!$this->isInBaseSchema($child_schema)) {
                $this->navigate($child_schema, $visited);
            }
        }

        return $this->class_datas;
    }

    /**
     *
     * @param Type $xsd_type
     * @param bool $force
     * @param bool $skip
     * @return PHPClass
     * @throws Exception
     */
    private function visitType(Type $xsd_type, bool $force = false, bool $skip = false): PHPClass
    {
        if (!$class_data = $this->getClassData($xsd_type)) {
            $class_data = $this->createClassData($xsd_type);
            $class = $class_data->getClass();

            $skip = $skip || $this->isInBaseSchema($xsd_type->getSchema());
            if ($alias = $this->getTypeAlias($xsd_type)) {
                $class->setName($alias);

                $class_data->skip();

                return $class;
            }

            [$name, $namespace_php] = $this->findPHPName($xsd_type);
            $class
                ->setName($name)
                ->setNamespace($namespace_php)
                ->setDoc($xsd_type->getDoc() . PHP_EOL . 'XSD Type: ' . ($xsd_type->getName() ?? 'anonymous'));

            $this->visitTypeBase($class, $xsd_type);

            if ($xsd_type instanceof SimpleType) {
                $class_data->skip();

                return $class;
            }

            if (!$force && ($this->isArrayType($xsd_type) || $this->isArrayNestedElement($xsd_type))) {
                $class_data->skip();

                return $class;
            }

            $class_data->skip($skip || (bool)$this->getTypeAlias($xsd_type));
        } else {
            if ($force && !($xsd_type instanceof SimpleType) && !$this->getTypeAlias($xsd_type)) {
                $class_data->skip($this->isInBaseSchema($xsd_type->getSchema()));
            }
        }

        return $class_data->getClass();
    }

    /**
     * @param object $obj
     * @return ClassData|null
     */
    private function getClassData(object $obj): ?ClassData
    {
        return ($this->class_datas[spl_object_hash($obj)] ?? null);
    }

    /**
     * @param object $obj
     * @return ClassData
     * @throws \RuntimeException
     */
    private function createClassData(object $obj): ClassData
    {
        if ($this->getClassData($obj)) {
            throw new RuntimeException('Hash exists');
        }

        return ($this->class_datas[spl_object_hash($obj)] = new ClassData(new PHPClass()));
    }

    /**
     * @param Schema $schema
     * @return bool
     */
    private function isInBaseSchema(Schema $schema): bool
    {
        return in_array($schema->getTargetNamespace(), $this->base_schemas, true);
    }

    /**
     * @param Type $type
     * @return array
     * @throws \RuntimeException
     */
    private function findPHPName(Type $type): array
    {
        $schema = $type->getSchema();

        if ($className = $this->getTypeAlias($type)) {
            if (($pos = strrpos($className, PHPClass::NS_SLASH)) !== false) {
                return [
                    substr($className, $pos + 1),
                    substr($className, 0, $pos)
                ];
            }

            return [
                $className,
                null
            ];
        }

        $name = $this->getNamingStrategy()->getTypeName($type);

        if (!isset($this->namespaces[$schema->getTargetNamespace()])) {
            throw new RuntimeException(sprintf("Can't find a PHP namespace to '%s' namespace", $schema->getTargetNamespace()));
        }

        $namespace = $this->namespaces[$schema->getTargetNamespace()];
        return [$name, $namespace];
    }

    /**
     * @param PHPClass $class
     * @param Type $type
     * @throws Exception
     */
    private function visitTypeBase(PHPClass $class, Type $type)
    {
        $class->setAbstract($type->isAbstract());

        if ($type instanceof SimpleType) {
            $this->visitSimpleType($class, $type);
        }
        if ($type instanceof BaseComplexType) {
            $this->visitBaseComplexType($class, $type);
        }
        if ($type instanceof ComplexType) {
            $this->visitComplexType($class, $type);
        }
    }

    /**
     * @param PHPClass $class
     * @param SimpleType $simple_type
     * @throws Exception
     */
    private function visitSimpleType(PHPClass $class, SimpleType $simple_type): void
    {
        if ($restriction = $simple_type->getRestriction()) {
            $restriction_type = $restriction->getBase();

            if ($restriction_type instanceof Type) {
                $this->handleBaseOfType($class, $restriction_type);
            }

            /** @var array[] $checks */
            foreach ($restriction->getChecks() as $type_check => $checks) {
                foreach ($checks as $check) {
                    $class->addCheck(PHPClass::VALUE_CLASS, $type_check, $check);
                }
            }
        } else {
            if ($unions = $simple_type->getUnions()) {
                $types = [];
                foreach ($unions as $i => $union) {
                    if (!$union->getName()) {
                        $types[] = $this->visitTypeAnonymous($union, $simple_type->getName() . $i, $class);
                    } else {
                        $types[] = $this->visitType($union);
                    }
                }

                if ($candidato = reset($types)) {
                    $class->setExtends($candidato);
                }
            }
        }
    }

    /**
     * @param PHPClass $class
     * @param Type $type
     * @throws Exception
     */
    private function handleBaseOfType(PHPClass $class, Type $type)
    {
        if ($alias = $this->getTypeAlias($type)) {
            $extends = PHPClass::createFromFQCN($alias);
            if ($extends === null) {
                throw new NullPointer;
            }

            $value_property = (new PHPProperty())
                ->setName(PHPClass::VALUE_CLASS)
                ->setType($extends);

            $extends->addProperty($value_property);
        } else {
            $extends = $this->visitType($type, true);
        }

        $class->setExtends($extends);
    }

    /**
     * @param Type $type
     * @param string $name
     * @param PHPClass $parent_class
     * @return \Madmages\Xsd\XsdToPhp\Php\Structure\PHPClass
     * @throws Exception
     */
    private function visitTypeAnonymous(Type $type, string $name, PHPClass $parent_class): PHPClass
    {
        if (!$class_data = $this->getClassData($type)) {
            $class_data = $this->createClassData($type);

            if ($type instanceof SimpleType) {
                $class_data->skip();
            }

            $class_data->getClass()
                ->setName($this->getNamingStrategy()->getAnonymousTypeName($type, $name))
                ->setNamespace($parent_class->getNamespace() . PHPClass::NS_SLASH . $parent_class->getName())
                ->setDoc($type->getDoc());

            $this->visitTypeBase($class_data->getClass(), $type);
        }

        return $class_data->getClass();
    }

    /**
     * @param PHPClass $class
     * @param BaseComplexType $type
     * @throws Exception
     */
    private function visitBaseComplexType(PHPClass $class, BaseComplexType $type)
    {
        if ($parent = $type->getParent()) {
            $base_of_parent = $parent->getBase();
            if ($base_of_parent instanceof Type) {
                $this->handleBaseOfType($class, $base_of_parent);
            }
        }

        $schema = $type->getSchema();
        foreach ($type->getAttributes() as $attribute) {
            if ($attribute instanceof AttributeGroup) {
                $this->visitAttributeGroup($class, $schema, $attribute);
            } else {
                $property = $this->visitAttribute($class, $attribute);
                $class->addProperty($property);
            }
        }
    }

    /**
     * @param PHPClass $class
     * @param Schema $schema
     * @param AttributeGroup $attribute_group
     * @throws Exception
     */
    private function visitAttributeGroup(PHPClass $class, Schema $schema, AttributeGroup $attribute_group)
    {
        foreach ($attribute_group->getAttributes() as $child_attribute) {
            if ($child_attribute instanceof AttributeGroup) {
                $this->visitAttributeGroup($class, $schema, $child_attribute);
            } else {
                $property = $this->visitAttribute($class, $child_attribute);
                $class->addProperty($property);
            }
        }
    }

    /**
     * @param PHPClass $class
     * @param AttributeSingle|AttributeDef $attribute
     * @return PHPProperty
     * @throws Exception
     */
    private function visitAttribute(PHPClass $class, $attribute): PHPProperty
    {
        $property = new PHPProperty();
        $property->setName($this->getNamingStrategy()->getPropertyName($attribute->getName()));
        if ($attribute instanceof Attribute) {
            $is_nullable = $attribute->getUse() === 'optional';
        } else {
            $is_nullable = true;
        }
        $property->setIsNullable($is_nullable);

        /** @var Attribute $attribute */
        $attribute_type = $attribute->getType();
        if ($attribute_type === null) {
            throw new NullPointer;
        }

        if ($item_of_array = $this->isArrayType($attribute_type)) {
            if ($attribute_type->getName()) {
                $arg = (new PHPProperty())->setName($this->getNamingStrategy()->getPropertyName($attribute->getName()));
                $arg->setType($this->visitType($item_of_array));

                $property->setType(new PHPClassOf($arg));
            } else {
                $property->setType($this->visitTypeAnonymous($attribute_type, $attribute->getName(), $class));
            }
        } else {
            $property->setType($this->findPHPClass($class, $attribute, true));
        }

        $property->setDoc($attribute->getDoc());
        return $property;
    }

    /**
     * @param PHPClass $class
     * @param Item $node
     * @param bool $force
     * @return bool|PHPClass|null
     * @throws Exception
     */
    private function findPHPClass(PHPClass $class, Item $node, bool $force = false)
    {
        if ($node instanceof ElementRef) {
            return $this->getClassByElement($node->getReferencedElement());
        }

        $node_type = $node->getType();
        if ($node_type === null) {
            throw new NullPointer;
        }

        if ($valueProp = $this->typeHasValue($node_type, $class, '')) {
            return $valueProp;
        }

        if (!$node_type->getName()) {
            return $this->visitTypeAnonymous($node_type, $node->getName(), $class);
        }

        return $this->visitType($node_type, $force);
    }

    /**
     * @param ElementDef $element
     * @param bool $skip
     * @return PHPClass
     * @throws Exception
     */
    private function getClassByElement(ElementDef $element, bool $skip = false): PHPClass
    {
        if (!$class_data = $this->getClassData($element)) {
            $class_data = $this->createClassData($element);
            $class = $class_data->getClass();

            $schema = $element->getSchema();
            $class_data->skip($skip || $this->isInBaseSchema($schema));

            if (!isset($this->namespaces[$target_ns = $schema->getTargetNamespace()])) {
                throw new RuntimeException(sprintf("Can't find a PHP namespace to '%s' namespace", $target_ns));
            }

            $class
                ->setDoc($element->getDoc())
                ->setName($this->getNamingStrategy()->getItemName($element->getName()))
                ->setNamespace($this->namespaces[$target_ns]);


            if (($element_type = $element->getType()) === null) {
                throw new NullPointer;
            }

            if (!$element_type->getName()) {
                $this->visitTypeBase($class, $element_type);
            } else {
                if ($alias = $this->getTypeAlias($element)) {
                    $class
                        ->setName($alias)
                        ->setNamespace(null);

                    $class_data->skip();

                    return $class;
                }

                $this->handleBaseOfType($class, $element_type);
            }
        }

        return $class_data->getClass();
    }

    /**
     * @param Type $type
     * @param PHPClass $parent_class
     * @param $name
     * @return bool|PHPClass|null
     * @throws Exception
     */
    private function typeHasValue(Type $type, PHPClass $parent_class, string $name)
    {
        do {
            if (!$type instanceof SimpleType) {
                return false;
            }

            if ($alias = $this->getTypeAlias($type)) {
                return PHPClass::createFromFQCN($alias);
            }

            if ($type->getName()) {
                $parent_class = $this->visitType($type);
            } else {
                $parent_class = $this->visitTypeAnonymous($type, $name, $parent_class);
            }

            if ($prop = $parent_class->getPropertyInHierarchy(PHPClass::VALUE_CLASS)) {
                return $prop->getType();
            }
        } while (
            (method_exists($type, 'getRestriction') && ($rest = $type->getRestriction()) && $type = $rest->getBase())
            ||
            (method_exists($type, 'getUnions') && ($unions = $type->getUnions()) && $type = reset($unions))
        );

        return false;
    }

    /**
     * @param PHPClass $class
     * @param ComplexType $complex_type
     * @throws Exception
     */
    private function visitComplexType(PHPClass $class, ComplexType $complex_type)
    {
        $schema = $complex_type->getSchema();
        /** @var Element $element */
        foreach ($complex_type->getElements() as $element) {
            if ($element instanceof Group) {
                $this->visitGroup($class, $schema, $element);
            } else {
                $property = $this->visitElement($class, $schema, $element);
                $class->addProperty($property);
            }
        }
    }

    /**
     * @param PHPClass $class
     * @param Schema $schema
     * @param Group $group
     * @throws Exception
     */
    private function visitGroup(PHPClass $class, Schema $schema, Group $group)
    {
        /** @var Element $childGroup */
        foreach ($group->getElements() as $childGroup) {
            if ($childGroup instanceof Group) {
                $this->visitGroup($class, $schema, $childGroup);
            } else {
                $property = $this->visitElement($class, $schema, $childGroup);
                $class->addProperty($property);
            }
        }
    }

    /**
     * @param PHPClass $class
     * @param Schema $schema
     * @param AbstractElementSingle $element
     * @param bool $arrayize
     * @return \Madmages\Xsd\XsdToPhp\Php\Structure\PHPProperty
     * @throws Exception
     * @throws NullPointer
     */
    private function visitElement(PHPClass $class, Schema $schema, AbstractElementSingle $element, bool $arrayize = true): PHPProperty
    {
        $element_class = $this->getNamingStrategy()->getPropertyName($element->getName());

        $property = (new PHPProperty())
            ->setIsNullable($element->getMin() === 0)
            ->setName($element_class)
            ->setDoc($element->getDoc());

        $type_of_element = $element->getType();

        if ($arrayize) {
            if ($type_of_element === null) {
                throw new NullPointer;
            }

            /** @var Type $item_of_array */
            if ($item_of_array = $this->isArrayType($type_of_element)) {
                if (!$item_of_array->getName()) {
                    if ($element instanceof ElementRef) {
                        $item_class = $this->findPHPClass($class, $element);
                    } else {
                        $item_class = $class;
                    }

                    $class_type = $this->visitTypeAnonymous($item_of_array, $element->getName(), $item_class);
                } else {
                    $class_type = $this->visitType($item_of_array);
                }

                return $property->setType(new PHPClassOf((new PHPProperty())->setName($element_class)->setType($class_type)));
            }

            /** @var Element $item_of_array */
            if ($item_of_array = $this->isArrayNestedElement($type_of_element)) {
                if (!$type_of_element->getName()) {
                    if ($element instanceof ElementRef) {
                        $item_class = $this->findPHPClass($class, $element);
                    } else {
                        $item_class = $class;
                    }

                    $class_type = $this->visitTypeAnonymous($type_of_element, $element->getName(), $item_class);
                } else {
                    $class_type = $this->visitType($type_of_element);
                }

                $element_property = $this->visitElement($class_type, $schema, $item_of_array, false);
                $property->setType(new PHPClassOf($element_property));

                return $property;
            }

            if ($this->isArrayElement($element)) {
                $arg = (new PHPProperty())
                    ->setName($element_class)
                    ->setType($this->findPHPClass($class, $element))
                    ->setDefault([]);

                return $property->setType(new PHPClassOf($arg));
            }
        }

        return $property->setType($this->findPHPClass($class, $element, true));
    }

    /**
     * @param ClassData[] $class_datas
     * @return PHPClass[]
     * @throws NullPointer
     */
    private function getTypes(array $class_datas): array
    {
        $result = [];
        foreach ($class_datas as $class_data) {
            if (!$class_data->isSkip()) {
                if (!$class = $class_data->getClass()) {
                    throw new NullPointer;
                }

                $result[$class->getFullName()] = $class;
            }
        }

        return $result;
    }
}