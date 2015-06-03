<?php

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Mapping\Metadata;
use Innmind\Neo4j\ONM\Mapping\Types;
use Innmind\Neo4j\ONM\Mapping\NodeMetadata;
use Innmind\Neo4j\ONM\Mapping\RelationshipMetadata;
use Innmind\Neo4j\ONM\Mapping\Property;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Doctrine\Common\Collections\ArrayCollection;

class Hydrator
{
    protected $map;
    protected $registry;
    protected $accessor;
    protected $entities;

    public function __construct(
        IdentityMap $map,
        MetadataRegistry $registry,
        EntitySilo $entities,
        PropertyAccessor $accessor
    ) {
        $this->map = $map;
        $this->registry = $registry;
        $this->entities = $entities;
        $this->accessor = $accessor;
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
        $id = $properties[$meta->getId()->getProperty()];

        if ($this->entities->has($class, $id)) {
            return $this->entities->get($class, $id);
        }

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

        $this->entities->add($entity, $class, $id);

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

            if (!$meta->hasStartNode() && !$meta->hasEndNode()) {
                continue;
            }

            if ($meta->hasStartNode() && isset($nodes[$startNodeId])) {
                $data[$startNodeId] = $nodes[$startNodeId];

                $this->bind(
                    $rel,
                    $meta,
                    $meta->getProperty($meta->getStartNode()),
                    $nodes[$startNodeId],
                    $nodeMetas[$startNodeId]
                );
            }

            if ($meta->hasEndNode() && isset($nodes[$endNodeId])) {
                $data[$endNodeId] = $nodes[$endNodeId];

                $this->bind(
                    $rel,
                    $meta,
                    $meta->getProperty($meta->getEndNode()),
                    $nodes[$endNodeId],
                    $nodeMetas[$endNodeId]
                );
            }
        }

        return $data;
    }

    /**
     * Associate a node to the relationship and vice versa
     *
     * @param object $relationship
     * @param RelationshipMetadata $relMeta
     * @param Property $property
     * @param object $node
     * @param NodeMetadata $meta
     *
     * @return void
     */
    protected function bind($relationship, RelationshipMetadata $relMeta, Property $property, $node, NodeMetadata $meta)
    {
        $this->accessor->setValue(
            $relationship,
            $property->getName(),
            $node
        );

        $properties = $meta->getProperties();

        foreach ($properties as $property) {
            if (
                $property->getType() !== 'relationship' ||
                $property->getOption('rel_type') !== $relMeta->getType()
            ) {
                continue;
            }

            $this->accessor->setValue(
                $node,
                $property->getName(),
                $relationship
            );
        }
    }
}
