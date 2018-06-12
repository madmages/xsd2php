<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Php\Structure;

class PHPArg
{
    /** @var string|null */
    protected $doc;

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

    public function getDoc(): ?string
    {
        return $this->doc;
    }

    public function setDoc(?string $doc): self
    {
        $this->doc = $doc;
        return $this;
    }

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
