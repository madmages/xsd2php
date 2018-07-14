<?php

namespace Madmages\Xsd\XsdToPhp;

use Madmages\Xsd\XsdToPhp\Contract\PHPType;

trait ClassPropertyTrait
{
    /** @var bool */
    protected $multiple = false;
    /** @var bool */
    protected $nullable = false;
    /** @var string */
    protected $default_value;
    /** @var PHPType */
    protected $type;
    /** @var ChunkValidator[] */
    protected $validators = [];

    /**
     * @param PHPType $type
     * @return ChunkProperty
     */
    public function setType(PHPType $type): self
    {
        return $this->set('type', $type);
    }

    public function setMultiple(bool $multiple = true): self
    {
        return $this->set('multiple', $multiple);
    }

    public function setNullable(bool $nullable = true): self
    {
        return $this->set('nullable', $nullable);
    }

    public function setDefaultValue(?string $default_value): self
    {
        return $this->set('default_value', $default_value);
    }

    public function getDefaultValue(): ?string
    {
        return $this->default_value;
    }

    /**
     * @return PHPType
     */
    public function getType(): PHPType
    {
        return $this->type;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function addValidator(ChunkValidator $validator): self
    {
        $this->validators[] = $validator;
        return $this;
    }

    public function getValidators(): array
    {
        return $this->validators;
    }
}