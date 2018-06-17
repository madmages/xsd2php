<?php

namespace Madmages\Xsd\XsdToPhp\DependencyInjection;

use Madmages\Xsd\XsdToPhp\Php\Structure\PHPClass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use GoetasWebservices\XML\XSDReader\SchemaReader;

class Xsd2PhpExtension extends Extension
{

    /**
     * @param array $configs
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $xml = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $xml->load('services.xml');

        $configuration = new Configuration();
        /** @var array[] $config */
        $config = $this->processConfiguration($configuration, $configs);
        foreach ($configs as $subConfig) {
            $config = array_merge($config, $subConfig);
        }

        $definition = $container->getDefinition('goetas_webservices.xsd2php.naming_convention.' . $config['naming_strategy']);
        $container->setDefinition('goetas_webservices.xsd2php.naming_convention', $definition);


        $schemaReader = $container->getDefinition(SchemaReader::class);
        foreach ($config['known_locations'] as $namespace => $location) {
            $schemaReader->addMethodCall('addKnownSchemaLocation', [$namespace, $location]);
        }

        foreach (['php', 'jms'] as $type) {
            $definition = $container->getDefinition('goetas_webservices.xsd2php.path_generator.' . $type . '.' . $config['path_generator']);
            $container->setDefinition('goetas_webservices.xsd2php.path_generator.' . $type, $definition);

            $pathGenerator = $container->getDefinition('goetas_webservices.xsd2php.path_generator.' . $type);
            $pathGenerator->addMethodCall('setTargets', [$config['destinations_' . $type]]);

            $converter = $container->getDefinition('goetas_webservices.xsd2php.converter.' . $type);
            foreach ($config['namespaces'] as $xml => $php) {
                $converter->addMethodCall('addNamespace', [$xml, self::sanitizePhp($php)]);
            }

            /** @var array $data */
            foreach ($config['aliases'] as $xml => $data) {
                foreach ($data as $dtype => $php) {
                    $converter->addMethodCall('addAliasMapType', [$xml, $dtype, self::sanitizePhp($php)]);
                }
            }
        }

        $container->setParameter('goetas_webservices.xsd2php.config', $config);
    }

    protected static function sanitizePhp($ns)
    {
        return str_replace('/', PHPClass::NS_SLASH, $ns);
    }

    public function getAlias(): string
    {
        return 'xsd2php';
    }
}
