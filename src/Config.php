<?php

namespace Madmages\Xsd\XsdToPhp;

use Madmages\Xsd\XsdToPhp\Components\Naming\LongNamingStrategy;
use Madmages\Xsd\XsdToPhp\Components\PathGenerator\Psr4;

class Config
{
    public const HANDLERS_CLASS = 'class';
    public const HANDLERS_METHOD = 'method';

    private $configs = [
        'namespaces'       => [],
        'destinations_php' => [],
        'destinations_jms' => [],
        'aliases'          => [],
        'validators'       => false,
        'types'            => false,
        'handlers'         => [
            self::HANDLERS_CLASS  => [],
            self::HANDLERS_METHOD => [],
        ],
        'naming_strategy'  => LongNamingStrategy::class,
        'path_generator'   => Psr4::class,
    ];

    public function addNamespace(
        string $xsd_namespace,
        string $php_namespace,
        string $path_php,
        string $path_jms,
        array $aliases = null
    ): self
    {
        $this->configs['namespaces'][$xsd_namespace] = $php_namespace;
        $this->configs['destinations_jms'][$php_namespace] = $path_jms;
        $this->configs['destinations_php'][$php_namespace] = $path_php;

        if ($aliases) {
            $this->configs['aliases'][$xsd_namespace] = $aliases;
        }

        return $this;
    }

    public function addAliases(string $namespace_xsd, array $types): self
    {
        $this->configs['aliases'][$namespace_xsd] = $types;

        return $this;
    }

    /**
     * @param string|null $namespace_xsd
     * @return string[]|string[][]
     */
    public function getAliases(string $namespace_xsd = null): array
    {
        if ($namespace_xsd) {
            return $this->configs['aliases'][$namespace_xsd];
        }

        return $this->configs['aliases'];
    }

    /**
     * Set callback handler for generated classes
     *
     * @param callable $handler
     * @return self
     */
    public function handleGeneratedClass(callable $handler): self
    {
        return $this->addHandler(self::HANDLERS_CLASS, $handler);
    }

    /**
     * Set callback handler for generated methods of class
     *
     * @param callable $handler
     * @return Config
     */
    public function handleGeneratedMethod(callable $handler): self
    {
        return $this->addHandler(self::HANDLERS_METHOD, $handler);
    }

    /**
     * Emits class\method handlers
     *
     * @param string $handlers_stack_name
     * @param $value
     * @return mixed|null
     */
    public function emitHandler(string $handlers_stack_name, $value)
    {
        $handlers = $this->configs['handlers'][$handlers_stack_name] ?? [];

        foreach ($handlers as $handler) {
            $value = $handler($value);
        }

        return $value ?? null;
    }

    /**
     * Returns XSD namespaces
     *
     * @return string[]
     */
    public function getNamespaces(): array
    {
        return $this->configs['namespaces'];
    }

    /**
     * @return string[]
     */
    public function getDestinationPHP(): array
    {
        return $this->configs['destinations_php'];
    }

    /**
     * @return string[]
     */
    public function getDestinationJMS(): array
    {
        return $this->configs['destinations_jms'];
    }

    public function getNamingStrategy(): string
    {
        return $this->configs['naming_strategy'];
    }

    /**
     * @param string $class
     * @return Config
     * @throws Exception\Config
     */
    public function setNamingStrategy(string $class): self
    {
        if (empty(class_implements($class)[Contract\NamingStrategy::class])) {
            throw new Exception\Config("class {$class} should implements " . Contract\NamingStrategy::class);
        }

        $this->configs['naming_strategy'] = $class;
        return $this;
    }

    /**
     * Returns class of PathGenerator
     *
     * @return string
     */
    public function getPathGenerator(): string
    {
        return $this->configs['path_generator'];
    }

    /**
     * @param string $class
     * @return self
     * @throws Exception\Config
     */
    public function setPathGenerator(string $class): self
    {
        if (!$class instanceof Contract\PathGenerator) {
            throw new Exception\Config("class {$class} should implements " . Contract\PathGenerator::class);
        }

        $this->configs['path_generator'] = $class;
        return $this;
    }

    public function setValidators(bool $enabled = true): self
    {
        $this->configs['validators'] = $enabled;
        return $this;
    }

    public function getValidators(): bool
    {
        return $this->configs['validators'];
    }

    public function setTypes(bool $enabled = true): self
    {
        $this->configs['types'] = $enabled;
        return $this;
    }

    public function getTypes(): bool
    {
        return $this->configs['types'];
    }

    //----------------- Private

    private function addHandler(string $handler_type, callable $handler): self
    {
        $this->configs['handlers'][$handler_type][] = $handler;

        return $this;
    }
}