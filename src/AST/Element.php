<?php

namespace Madmages\Xsd\XsdToPhp\AST;

use GoetasWebservices\XML\XSDReader\Schema\Element\Element as RElement;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementSingle;
use Madmages\Xsd\XsdToPhp\Chunk;
use Madmages\Xsd\XsdToPhp\ChunkClass;
use Madmages\Xsd\XsdToPhp\ChunkProperty;
use Madmages\Xsd\XsdToPhp\ChunkValidator;
use Madmages\Xsd\XsdToPhp\Exception\NullPointer;
use RuntimeException;

class Element
{
    /**
     * @param ElementItem|ElementSingle|ElementDef $element
     * @param bool $standalone
     * @return ChunkProperty|ChunkClass
     * @throws RuntimeException
     * @throws \Madmages\Xsd\XsdToPhp\Exception\Exception
     * @throws \Madmages\Xsd\XsdToPhp\Exception\NullPointer
     */
    public static function handle(ElementItem $element, bool $standalone = false): Chunk
    {
        if ($standalone) {
            $element_class = ChunkClass::create($element);
            if ($element_class === null) {
                throw new RuntimeException('unexpected');
            }

            $element_class->setName(Helper::getNamingStrategy()->getElementName($element->getName()))
                ->setPHPNamespace(Helper::getPHPNamespace($element))
                ->setXSDNamespace($element->getSchema()->getTargetNamespace())
                ->setAbstract(false)
                ->setComment($element->getDoc());

            $element_type = $element->getType();
            if ($element_type === null) {
                throw new NullPointer();
            }

            $php_type = Type::handle($element_type, $element);
            if ($php_type instanceof SimplePHPType) {
                throw new RuntimeException('unexpected');
            }

            /** @var ChunkClass $php_type */
            $element_class->setParent($php_type);

            return $element_class;
        }

        $result_type = Type::handle($element->getType(), $element);
        $property = ChunkProperty::create($element)
            ->setName(Helper::getNamingStrategy()->getPropertyName($element->getName()))
            ->setNullable($element->getMin() === 0)
            ->setMultiple($element->getMax() === -1 || $element->getMax() > 1)
            ->setType($result_type);

        if ($result_type instanceof ChunkValidator) {
            $property->addValidator($result_type);
        }

        if ($element instanceof RElement) {
            $property->setComment($element->getDoc());
        }

        return $property;
    }

    public static function handleGroup(ElementSingle $element): ChunkProperty
    {
        echo 1;
    }
}
