<?php

namespace Madmages\Xsd\XsdToPhp\Command;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class Convert extends Command
{
    /** @var ContainerBuilder */
    protected $container;

    /**
     * Convert constructor.
     * @param ContainerInterface $container
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct();
    }

    /**
     * @see Command
     * @throws InvalidArgumentException
     */
    protected function configure(): void
    {
        $this->setName('convert');
        $this->setDescription('Convert a XSD file into PHP classes and JMS serializer metadata files');
        $this->setDefinition([
            new InputArgument('config', InputArgument::REQUIRED, 'Where is located your XSD definitions'),
            new InputArgument('src', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Where is located your XSD definitions'),
        ]);
    }

    /**
     * @see Command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     * @throws InvalidArgumentException
     * @throws \Symfony\Component\Config\Exception\FileLoaderLoadException
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->loadConfigurations($input->getArgument('config'));

        /** @var string[] $src */
        $src = $input->getArgument('src');

        $schemas = [];
        $reader = $this->container->get('goetas_webservices.xsd2php.schema_reader');
        foreach ($src as $file) {
            $schemas[] = $reader->readFile($file);
        }

        $items = [];
        foreach (['php', 'jms'] as $type) {
            $items = $this->container
                ->get('goetas_webservices.xsd2php.converter.' . $type)
                ->convert($schemas);

            $this->container
                ->get('goetas_webservices.xsd2php.writer.' . $type)
                ->write($items);
        }

        return count($items) ? 0 : 255;
    }

    /**
     * @param $configFile
     * @throws \Symfony\Component\Config\Exception\FileLoaderLoadException|\Exception
     */
    protected function loadConfigurations($configFile): void
    {
        $locator = new FileLocator('.');

        $yaml = new YamlFileLoader($this->container, $locator);
        $xml = new XmlFileLoader($this->container, $locator);

        (new DelegatingLoader(new LoaderResolver([$yaml, $xml])))->load($configFile);

        $this->container->compile();
    }
}
