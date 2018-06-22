<?php

namespace Madmages\Xsd\XsdToPhp;

use Madmages\Xsd\XsdToPhp\Components\Naming\ShortNamingStrategy;
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
        'handlers'         => [
            self::HANDLERS_CLASS  => [],
            self::HANDLERS_METHOD => [],
        ],
        'naming_strategy'  => ShortNamingStrategy::class,
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

    public function handleGeneratedClass(callable $handler): self
    {
        return $this->addHandler(self::HANDLERS_CLASS, $handler);
    }

    public function handleGeneratedMethod(callable $handler): self
    {
        return $this->addHandler(self::HANDLERS_METHOD, $handler);
    }

    public function emitHandler(string $handlers_stack_name, $value)
    {
        $handlers = $this->configs['handlers'][$handlers_stack_name] ?? [];

        foreach ($handlers as $handler) {
            $value = $handler($value);
        }

        return $value;
    }

    /**
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

    private function addHandler(string $handler_type, callable $handler)
    {
        $this->configs['handlers'][$handler_type][] = $handler;

        return $this;
    }

    public function getNamingStrategy(): string
    {
        return $this->configs['naming_strategy'];
    }

    public function getPathGenerator(): string
    {
        return $this->configs['path_generator'];
    }
}