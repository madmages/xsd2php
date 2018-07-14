<?php

namespace Madmages\Xsd\XsdToPhp\AST;


use GoetasWebservices\XML\XSDReader\Schema\Attribute\AbstractAttributeItem;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute as RAttribute;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItem;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeSingle;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Group;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;
use Madmages\Xsd\XsdToPhp\ChunkProperty;
use Madmages\Xsd\XsdToPhp\ChunkValidator;
use Madmages\Xsd\XsdToPhp\Exception\NullPointer;
use RuntimeException;

class Attribute
{
    /**
     * @param AttributeItem|AbstractAttributeItem $attribute
     * @return ChunkProperty
     * @throws RuntimeException
     * @throws NullPointer
     * @throws \Madmages\Xsd\XsdToPhp\Exception\Exception
     */
    public static function handle(AttributeItem $attribute): ChunkProperty
    {
        $property = ChunkProperty::create($attribute)
            ->setComment($attribute->getDoc())
            ->setDefaultValue($attribute->getDefault())
            ->setMultiple(false)
            ->setName(Helper::getNamingStrategy()->getPropertyName($attribute->getName()));
        if ($property === null) {
            throw new NullPointer();
        }

        if ($attribute instanceof RAttribute) {
            $property->setNullable($attribute->getUse() === AttributeSingle::USE_OPTIONAL);
        } else {
            $property->setNullable(false);
        }

        /** @var Attribute $attribute */
        $attribute_type = $attribute->getType();
        if ($attribute_type === null) {
            throw new NullPointer;
        }

        if (Type::isArray($attribute_type)) {
            if (!$attribute_type instanceof SimpleType) {
                throw new RuntimeException('unexpected');
            }

            $property->setMultiple();

            $result_type = Type::handle($attribute_type->getList());
        } else {
            $result_type = Type::getPropertyType($attribute);
        }

        $property->setType($result_type);
        if ($result_type instanceof ChunkValidator) {
            $property->addValidator($result_type);
        }

        return $property;
    }

    /**
     * @param Group $group
     * @return ChunkProperty[]
     * @throws RuntimeException
     * @throws NullPointer
     * @throws \Madmages\Xsd\XsdToPhp\Exception\Exception
     */
    public static function handleGroup(Group $group): array
    {
        $result = [];
        foreach ($group->getAttributes() as $child_attribute) {
            if ($child_attribute instanceof Group) {
                $result = array_merge($result, self::handleGroup($child_attribute));
            } else {
                $result[] = self::handle($child_attribute);
            }
        }

        return $result;
    }
}
