<?php

namespace Madmages\Xsd\XsdToPhp\Command;

use GoetasWebservices\XML\XSDReader\SchemaReader;
use Madmages\Xsd\XsdToPhp\App;
use Madmages\Xsd\XsdToPhp\Components\Naming\ShortNamingStrategy;
use Madmages\Xsd\XsdToPhp\Jms\PathGenerator\Psr4PathGenerator as Psr4PathGeneratorJMS;
use Madmages\Xsd\XsdToPhp\NamingStrategy;
use Madmages\Xsd\XsdToPhp\PathGenerator;
use Madmages\Xsd\XsdToPhp\Php\PathGenerator\Psr4PathGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Convert extends Command
{
    protected $reader;

    /**
     * Convert constructor.
     * @param SchemaReader $reader
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(SchemaReader $reader)
    {
        $this->reader = $reader;
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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \GoetasWebservices\XML\XSDReader\Exception\IOException
     * @throws \Illuminate\Container\EntryNotFoundException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        /** @var string $config_file */
        $config_file = $input->getArgument('config');

        /** @var string[] $src */
        $src = $input->getArgument('src');

        $schemas = [];
        foreach ($src as $file) {
            $schemas[] = $this->reader->readFile($file);
        }

        App::getInstance()->singleton(NamingStrategy::class, ShortNamingStrategy::class);
        App::getInstance()->singleton(PathGenerator::class . App::PHP, Psr4PathGenerator::class);
        App::getInstance()->singleton(PathGenerator::class . App::JMS, Psr4PathGeneratorJMS::class);

        $items = [];
        foreach ([App::PHP, App::JMS] as $type) {
            App::convertAndWrite($type, $schemas);
        }

        return 0;
    }
}
