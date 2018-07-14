<?php

namespace Madmages\Xsd\XsdToPhp\AST;


use GoetasWebservices\XML\XSDReader\Schema\Attribute\Group as AttributeGroup;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementRef;
use GoetasWebservices\XML\XSDReader\Schema\Element\Group as ElementGroup;
use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\Schema\Type\BaseComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexTypeSimpleContent;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type as RType;
use Madmages\Xsd\XsdToPhp\ChunkClass;
use Madmages\Xsd\XsdToPhp\ChunkClassProperty;
use Madmages\Xsd\XsdToPhp\ChunkValidator;
use Madmages\Xsd\XsdToPhp\Contract\PHPType;
use Madmages\Xsd\XsdToPhp\Exception\Exception;
use Madmages\Xsd\XsdToPhp\Exception\NullPointer;
use Madmages\Xsd\XsdToPhp\Types;
use RuntimeException;

class Type
{
    /**
     * @param RType $xsd_type
     * @param Item|null $parent
     * @return PHPType|ChunkValidator
     * @throws \Madmages\Xsd\XsdToPhp\Exception\NullPointer
     * @throws RuntimeException
     * @throws Exception
     */
    public static function handle(RType $xsd_type, Item $parent = null): PHPType
    {
        if ($simple_php_type = Helper::getTypeAlias($xsd_type)) {
            /** @var SimplePHPType $simple_php_type */
            return $simple_php_type;
        }

        if ($php_type = ChunkClass::get($xsd_type)) {
            /** @var ChunkClass|ChunkClassProperty $php_type */
            return $php_type;
        }

        if ($php_validator = ChunkValidator::get($xsd_type)) {
            /** @var ChunkValidator $php_validator */
            return $php_validator;
        }

        if ($xsd_type instanceof SimpleType) {
            return self::handleSimpleType($xsd_type, $parent);
        }

        if ($xsd_type instanceof ComplexType) {
            return self::handleComplexType($xsd_type, $parent);
        }

        if ($xsd_type instanceof BaseComplexType) {
            return self::handleBaseComplexType($xsd_type, $parent);
        }

        throw new RuntimeException('unexpected');
    }

    /**
     * @param SimpleType $simple_type
     * @param Item|null $parent
     * @return ChunkClassProperty
     * @throws \Madmages\Xsd\XsdToPhp\Exception\NullPointer
     * @throws \Madmages\Xsd\XsdToPhp\Exception\Exception
     * @throws \RuntimeException
     */
    private static function handleSimpleType(SimpleType $simple_type, ?Item $parent): ChunkValidator
    {
        $property_validator = ChunkValidator::create($simple_type);
        if ($property_validator === null) {
            throw new RuntimeException('unexpected');
        }

        [$name, $namespace_php] = Helper::resolveTypeNames($simple_type, $parent);
        $property_validator
            ->setName($name)
            ->setPHPNamespace($namespace_php)
            ->setXSDNamespace($simple_type->getSchema()->getTargetNamespace())
            ->setComment($simple_type->getDoc());

        if ($restrictions = $simple_type->getRestriction()) {
            if ($restriction_base_type = $restrictions->getBase()) {

                $restriction_type = Helper::getTypeAlias($restriction_base_type);
                if ($restriction_type === null) {

                    $restriction_type = self::handle($restriction_base_type);
                    if ($restriction_type instanceof ChunkValidator) {

                        $property_validator->setParent($restriction_type);
                    }
                }
            } else {
                $restriction_type = SimplePHPType::create(Types::STRING);
            }

            return $property_validator
                ->setType($restriction_type)
                ->addChecks($restrictions->getChecks());
        }

        if ($union_types = $simple_type->getUnions()) {
            return $property_validator
                ->setType(new SimplePHPType(Types::STRING))//TODO fix union type
                ->setUnions($union_types);
        }

        if ($type_of_list_item = $simple_type->getList()) {
            $result_type = self::handle($type_of_list_item);
            if ($result_type instanceof ChunkValidator) {
                $property_validator->setParent($result_type);
            }

            return $property_validator
                ->setType($result_type)
                ->setList();
        }

        throw new RuntimeException('unexpected');
    }

    /**
     * @param BaseComplexType $complex_type
     * @param Item|null $parent
     * @return ChunkClassProperty
     * @throws \RuntimeException
     * @throws Exception
     * @throws NullPointer
     */
    private static function handleBaseComplexType(BaseComplexType $complex_type, ?Item $parent): ChunkClass
    {
        if (!$complex_type instanceof ComplexTypeSimpleContent) {
            throw new RuntimeException('unexpected');
        }

        if ($parent_type = $complex_type->getParent()) {
            $base_of_parent = $parent_type->getBase();
            if ($base_of_parent instanceof SimpleType) {
                $property_or_class = ChunkClassProperty::create($complex_type);
            } else {
                if ($base_of_parent instanceof ComplexTypeSimpleContent) {
                    $property_or_class = ChunkClass::create($complex_type);
                } else {
                    throw new RuntimeException('unexpected');
                }
            }

            if ($property_or_class === null) {
                throw new RuntimeException('unexpected');
            }

            self::fillClassBaseData($complex_type, $property_or_class, $parent);

            $extends = self::handle($base_of_parent);
            if ($extends instanceof ChunkValidator) {
                $property_or_class
                    ->setType($extends)
                    ->addValidator($extends);
            } else {
                if ($extends instanceof ChunkClass) {
                    $property_or_class->setParent($extends);
                } else {
                    if (
                        $extends instanceof SimplePHPType
                        && $property_or_class instanceof ChunkClassProperty
                    ) {
                        $property_or_class->setType($extends);
                    } else {
                        throw new RuntimeException('unexpected');
                    }
                }
            }
        } else {
            throw new RuntimeException('unexpected');
        }

        $attributes = [];
        foreach ($complex_type->getAttributes() as $attribute) {
            if ($attribute instanceof AttributeGroup) {
                $attributes = array_merge($attributes, Attribute::handleGroup($attribute));
            } else {
                $attributes[] = Attribute::handle($attribute);
            }
        }

        foreach ($attributes as $property) {
            $property_or_class->addProperty($property);
        }

        return $property_or_class;
    }

    public static function isArray(RType $type): bool
    {
        if ($type instanceof SimpleType) {
            return (bool)$type->getList();
        }

        return false;
    }

    /**
     * @param Item $item
     * @return PHPType
     * @throws RuntimeException
     * @throws NullPointer
     * @throws Exception
     */
    public static function getPropertyType(Item $item): PHPType
    {
        if ($item instanceof ElementRef) {
            throw new RuntimeException('unexpected');
            //return $this->getClassByElement($node->getReferencedElement());
        }

        $node_type = $item->getType();
        if ($node_type === null) {
            throw new NullPointer;
        }

        if ($node_type->getName() === null) {
            return self::handle($node_type, $item);
        }

        return self::handle($node_type);
    }

    /**
     * @param RType $xsd_type
     * @param Item $parent
     * @param $chunk_class
     * @throws \RuntimeException
     */
    private static function fillClassBaseData(RType $xsd_type, ChunkClass $chunk_class, ?Item $parent): void
    {
        [$name, $namespace_php] = Helper::resolveTypeNames($xsd_type, $parent);
        $chunk_class
            ->setName($name)
            ->setPHPNamespace($namespace_php)
            ->setXSDNamespace($xsd_type->getSchema()->getTargetNamespace())
            ->setComment($xsd_type->getDoc())
            ->setAbstract($xsd_type->isAbstract());
    }

    /**
     * @param ComplexType $complex_type
     * @param Item|null $parent
     * @return ChunkClass
     * @throws \Madmages\Xsd\XsdToPhp\Exception\NullPointer
     * @throws \RuntimeException
     * @throws Exception
     */
    private static function handleComplexType(ComplexType $complex_type, ?Item $parent): ChunkClass
    {
        $chunk_class = ChunkClass::create($complex_type);
        if ($chunk_class === null) {
            throw new RuntimeException('unexpected');
        }
        self::fillClassBaseData($complex_type, $chunk_class, $parent);

        if ($parent_type = $complex_type->getParent()) {
            $base_of_parent = $parent_type->getBase();
            if (!$base_of_parent instanceof RType) {
                throw new Exception('unexpected');
            }

            $extends = self::handle($base_of_parent);
            if ($extends instanceof ChunkClass) {
                $chunk_class->setParent($extends);
            } else {
                throw new RuntimeException('unexpected');
            }
        }

        $properties = [];
        foreach ($complex_type->getElements() as $element) {
            if ($element instanceof ElementGroup) {
                throw new RuntimeException('unexpected');
                //$properties = array_merge($properties, Element::handleGroup($class, $schema, $element));
            } else {
                $properties[] = Element::handle($element);
            }
        }

        foreach ($complex_type->getAttributes() as $attribute) {
            if ($attribute instanceof AttributeGroup) {
                $properties = array_merge($properties, Attribute::handleGroup($attribute));
            } else {
                $properties[] = Attribute::handle($attribute);
            }
        }

        foreach ($properties as $property) {
            $chunk_class->addProperty($property);
        }

        return $chunk_class;
    }
}
