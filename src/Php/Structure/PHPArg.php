<?php

namespace Madmages\Xsd\XsdToPhp\Php\Structure;

class PHPArg
{
    /** @var string|null */
    protected $doc;

    /** @var bool */
    protected $is_nullable = false;

    /** @var PHPClass|null */
    protected $type;

    /** @var string|null */
    protected $name;

    /** @var array|null */
    protected $default;

    public function __construct(string $name = null, PHPClass $type = null)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function setIsNullable(bool $is_nullable): self
    {
        $this->is_nullable = $is_nullable;
        return $this;
    }

    public function getIsNullable(): bool
    {
        return $this->is_nullable;
    }

    public function getDoc(): ?string
    {
        return $this->doc;
    }

    public function setDoc(?string $doc): self
    {
        $this->doc = $doc;
        return $this;
    }

    /**
     * @return static|null
     */
    public function getType(): ?PHPClass
    {
        return $this->type;
    }

    public function setType(PHPClass $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDefault(): ?array
    {
        return $this->default;
    }

    public function setDefault(array $default)
    {
        $this->default = $default;
        return $this;
    }
}
