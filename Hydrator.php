<?php

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Mapping\Metadata;
use Innmind\Neo4j\ONM\Mapping\Types;
use Innmind\Neo4j\ONM\Mapping\Property;
use Innmind\Neo4j\ONM\Mapping\NodeMetadata;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Doctrine\Common\Collections\ArrayCollection;

class Hydrator
{
    protected $map;
    protected $registry;
    protected $accessor;

    public function __construct(IdentityMap $map, MetadataRegistry $registry)
    {
        $this->map = $map;
        $this->registry = $registry;
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * Take the results for the given query an build a collection of appropriate entities
     *
     * @param array $results
     * @param Query $query
     *
     * @return ArrayCollection
     */
    public function hydrate(array $results, Query $query)
    {
        $nodes = [];
        $rels = [];
        $nodeMetas = [];
        $relMetas = [];
        $variables = $query->getVariables();

        foreach ($results['rows'] as $variable => $values) {
            $class = $this->map->getClass($variables[$variable]);
            $metadata = $this->registry->getMetadata($class);

            foreach ($values as $value) {
                $entity = $this->createEntity($metadata, $value);
                $realId = null;

                if ($metadata instanceof NodeMetadata) {
                    foreach ($results['nodes'] as $id => $node) {
                        $labels = $metadata->getLabels();

                        if (count($labels) !== count(array_intersect($labels, $node['labels']))) {
                            continue;
                        }

                        $idProp = $metadata->getId()->getProperty();

                        if ($value[$idProp] !== $node['properties'][$idProp]) {
                            continue;
                        }

                        $realId = $node['id'];
                        $nodes[$realId] = $entity;
                        $nodeMetas[$realId] = $metadata;
                        break;
                    }
                } else {
                    foreach ($results['relationships'] as $id => $rel) {
                        if ($metadata->getType() !== $rel['type']) {
                            continue;
                        }

                        $idProp = $metadata->getId()->getProperty();

                        if ($value[$idProp] !== $rel['properties'][$idProp]) {
                            continue;
                        }

                        $realId = $rel['id'];
                        $rels[$realId] = $entity;
                        $relMetas[$realId] = $metadata;
                        break;
                    }
                }
            }
        }

        $entities = $this->associate($nodes, $rels, $results, $nodeMetas, $relMetas);

        return new ArrayCollection(array_values($entities));
    }

    /**
     * Create an entity
     *
     * @param Metadata $meta
     * @param array $properties
     *
     * @return object
     */
    public function createEntity(Metadata $meta, array $properties)
    {
        $class = $meta->getClass();
        $entity = new $class;

        foreach ($meta->getProperties() as $property) {
            if (!isset($properties[$property->getName()])) {
                continue;
            }

            $type = Types::getType($property->getType());

            $this->accessor->setValue(
                $entity,
                $property->getName(),
                $type->convertToPHPValue(
                    $properties[$property->getName()],
                    $property
                )
            );
        }

        return $entity;
    }

    /**
     * Inject the relationships into nodes and vice versa
     *
     * @param array $nodes
     * @param array $rels
     * @param array $results
     * @param array $nodeMetas
     * @param array $relMetas
     *
     * @return void
     */
    protected function associate(array $nodes, array $rels, array $results, array $nodeMetas, array $relMetas)
    {
        $data = [];

        if (empty($rels)) {
            return $nodes;
        }

        foreach ($rels as $id => $rel) {
            $startNodeId = $results['relationships'][$id]['startNode'];
            $endNodeId = $results['relationships'][$id]['endNode'];

            $meta = $relMetas[$id];
            $properties = $meta->getProperties();

            foreach ($properties as $property) {
                if (!in_array($property->getType(), ['startNode', 'endNode'], true)) {
                    continue;
                }

                if ($property->getType() === 'startNode') {
                    $node = $nodes[$startNodeId];
                    $nodeMeta = $nodeMetas[$startNodeId];
                    $data[$startNodeId] = $node;
                } else {
                    $node = $nodes[$endNodeId];
                    $nodeMeta = $nodeMetas[$endNodeId];
                    $data[$endNodeId] = $node;
                }

                $this->accessor->setValue(
                    $rel,
                    $property->getName(),
                    $node
                );

                $nodeProperties = $nodeMeta->getProperties();
                foreach ($nodeProperties as $nodeProperty) {
                    if (
                        $nodeProperty->getType() !== 'relationship' ||
                        $nodeProperty->getOption('rel_type') !== $meta->getType()
                    ) {
                        continue;
                    }

                    $this->accessor->setValue(
                        $node,
                        $nodeProperty->getName(),
                        $rel
                    );
                }
            }
        }

        return $data;
    }
}
