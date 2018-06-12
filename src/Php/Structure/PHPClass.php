<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Php\Structure;

use GoetasWebservices\Xsd\XsdToPhp\Php\Types;

class PHPClass
{
    public const NS_SLASH = '\\';

    /** @var string|null */
    protected $name;

    /** @var string|null */
    protected $namespace;

    /** @var string|null */
    protected $doc;

    /** @var array[][] */
    protected $checks = [];

    /** @var PHPProperty[] */
    protected $properties = [];

    /** @var bool */
    protected $abstract = false;

    /** @var self|null */
    protected $extends;

    public function __construct(string $name = null, string $namespace = null)
    {
        $this->name = $name;
        $this->namespace = $namespace;
    }

    public static function createFromFQCN(string $class_name): ?self
    {
        if (($pos = strrpos($class_name, self::NS_SLASH)) !== false) {
            return new self(substr($class_name, $pos + 1), substr($class_name, 0, $pos));
        }

        return new self($class_name);
    }

    /**
     * @param bool $only_parent
     * @return PHPProperty|null
     */
    public function getSimpleType(bool $only_parent = false): ?PHPProperty
    {
        if ($only_parent) {
            if (($extends = $this->getExtends()) && $extends->hasProperty('__value')) {
                return $extends->getProperty('__value');
            }
        } else {
            if ($this->hasPropertyInHierarchy('__value') && count($this->getPropertiesInHierarchy()) === 1) {
                return $this->getPropertyInHierarchy('__value');
            }
        }

        return null;
    }

    public function getExtends(): ?self
    {
        return $this->extends;
    }

    public function setExtends(self $extends): self
    {
        $this->extends = $extends;
        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasProperty(string $name): bool
    {
        return isset($this->properties[$name]);
    }

    /**
     * @param string $name
     * @return PHPProperty
     */
    public function getProperty(string $name): PHPProperty
    {
        return $this->properties[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasPropertyInHierarchy(string $name): bool
    {
        if ($this->hasProperty($name)) {
            return true;
        }

        if (!$extends = $this->getExtends()) {
            return false;
        }

        if ($this instanceof self && $extends->hasPropertyInHierarchy($name)) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getPropertiesInHierarchy(): array
    {
        $properties = $this->getProperties();

        if ($this instanceof self && ($extends = $this->getExtends())) {
            $properties = array_merge($properties, $extends->getPropertiesInHierarchy());
        }

        return $properties;
    }

    /**
     * @return PHPProperty[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getPropertyInHierarchy(string $name): ?PHPProperty
    {
        if ($this->hasProperty($name)) {
            return $this->getProperty($name);
        }

        if (!$extends = $this->getExtends()) {
            return null;
        }

        if ($this instanceof self && $extends->hasPropertyInHierarchy($name)) {
            return $extends->getPropertyInHierarchy($name);
        }

        return null;
    }

    public function getPhpType(bool $with_mixed = true, bool $is_nullable = false): ?string
    {
        if (!$this->getNamespace()) {
            if ($this->isNativeType()) {
                if ($with_mixed) {
                    $result = $this->getName();
                } else {
                    $type = $this->getName();
                    $result = ($type !== Types::MIXED ? $type : null);
                }
            } else {
                $result = self::NS_SLASH . $this->getName();
            }
        } else {
            $result = self::NS_SLASH . $this->getFullName();
        }

        return ($result ? ($is_nullable ? '?' : '') . $result : null);
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;
        return $this;
    }

    public function isNativeType(): bool
    {
        return !$this->getNamespace() && in_array(
                $this->getName(),
                [
                    Types::STRING,
                    Types::INT,
                    Types::FLOAT,
                    Types::BOOL,
                    Types::ARRAY,
                    Types::CALLABLE,

                    Types::MIXED //todo this is not a php type but it's needed for now to allow mixed return tags
                ], true);
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

    public function getFullName(): string
    {
        return $this->namespace . self::NS_SLASH . $this->name;
    }

    public function getDoc(): string
    {
        return $this->doc;
    }

    public function setDoc(string $doc): self
    {
        $this->doc = $doc;
        return $this;
    }

    public function getChecks(string $property): array
    {
        return ($this->checks[$property] ?? []);
    }

    public function addCheck(string $property, string $check, array $value): self
    {
        $this->checks[$property][$check][] = $value;
        return $this;
    }

    public function addProperty(PHPProperty $property): self
    {
        $this->properties[$property->getName()] = $property;
        return $this;
    }

    public function getAbstract(): bool
    {
        return $this->abstract;
    }

    public function setAbstract(bool $abstract): self
    {
        $this->abstract = $abstract;
        return $this;
    }

    public function __toString()
    {
        return $this->getFullName();
    }
}