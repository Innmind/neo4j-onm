<?php

namespace Innmind\Neo4j\ONM\Mapping\Reader;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Innmind\Neo4j\ONM\Mapping\Id;
use Innmind\Neo4j\ONM\Mapping\Property;
use Innmind\Neo4j\ONM\Mapping\ReaderInterface;
use Innmind\Neo4j\ONM\Mapping\NodeMetadata;
use Innmind\Neo4j\ONM\Mapping\RelationshipMetadata;
use Symfony\Component\Config\Definition\Processor;

class YamlReader implements ReaderInterface
{
    protected $resources = [];
    protected $processor;
    protected $config;

    public function __construct()
    {
        $this->processor = new Processor;
        $this->config = new FileConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    public function load($location)
    {
        $files = $this->getResources($location);
        $metas = [];

        foreach ($files as $file) {
            $content = Yaml::parse(file_get_contents($file));

            $content = $this->processor->processConfiguration(
                $this->config,
                [$content]
            );

            foreach ($content as $class => $meta) {
                $metas[] = $this->buildMetadata($class, $meta);
            }
        }

        return $metas;
    }

    /**
     * {@inheritdoc}
     */
    public function getResources($location)
    {
        if (isset($this->resources[(string) $location])) {
            return $this->resources[(string) $location];
        }

        if (is_dir($location)) {
            $resources = [];
            $finder = new Finder;
            $finder
                ->files()
                ->in($location)
                ->name('/\.ya?ml$/');

            foreach ($finder as $file) {
                $resources[] = $file->getRealpath();
            }

            $this->resources[(string) $location] = $resources;
        } else {
            $this->resources[(string) $location] = [realpath($location)];
        }

        return $this->resources[(string) $location];
    }

    /**
     * Build the metadata object for the given class
     *
     * @param string $class
     * @param array $config
     *
     * @return Metadata
     */
    protected function buildMetadata($class, $config)
    {
        switch ($config['type']) {
            case 'relationship':
                $metadata = new RelationshipMetadata;
                $this->configureRelationship($metadata, $config);
                break;
            default:
                $metadata = new NodeMetadata;
                $this->configureNode($metadata, $config);
        }

        $id = new Id;
        $id
            ->setProperty(array_keys($config['id'])[0])
            ->setType($config['id'][$id->getProperty()]['type'])
            ->setStrategy($config['id'][$id->getProperty()]['generator']['strategy']);

        $metadata
            ->setClass($class)
            ->setId($id);

        if (isset($config['repository'])) {
            $metadata->setRepositoryClass($config['repository']);
        }

        if (isset($config['alias'])) {
            $metadata->setAlias($config['alias']);
        }

        if (isset($config['properties'])) {
            foreach ($config['properties'] as $prop => $conf) {
                $property = new Property;
                $property
                    ->setName($prop)
                    ->setType($conf['type']);

                if (isset($conf['nullable'])) {
                    $property->setNullable($conf['nullable']);
                    unset($conf['nullable']);
                }

                unset($conf['type']);

                foreach ($conf as $key => $value) {
                    $property->addOption($key, $value);
                }

                $metadata->addProperty($property);
            }
        }

        return $metadata;
    }

    /**
     * Configure a relationship related config
     *
     * @param RelationshipMetadata $meta
     * @param array $config
     *
     * @return void
     */
    protected function configureRelationship(RelationshipMetadata $meta, array $config)
    {
        $meta->setType($config['rel_type']);
    }

    /**
     * Configure a node related config
     *
     * @param NodeMetadata $meta
     * @param array $config
     *
     * @return void
     */
    protected function configureNode(NodeMetadata $meta, array $config)
    {
        foreach ($config['labels'] as $label) {
            $meta->addLabel($label);
        }
    }
}
