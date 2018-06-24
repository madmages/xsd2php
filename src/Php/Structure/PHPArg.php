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

    /**
     * @param bool $is_nullable
     * @return static
     */
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

    /**
     * @param null|string $doc
     * @return static
     */
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

    /**
     * @param PHPClass $type
     * @return static
     */
    public function setType(PHPClass $type): self
    {
        $this->type = $type;
        return $this;
    }


    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return static
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDefault(): ?array
    {
        return $this->default;
    }

    /**
     * @param array $default
     * @return static
     */
    public function setDefault(array $default):self
    {
        $this->default = $default;
        return $this;
    }
}
