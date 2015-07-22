<?php

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Mapping\Metadata;
use Innmind\Neo4j\ONM\Mapping\Types;
use Innmind\Neo4j\ONM\Mapping\NodeMetadata;
use Innmind\Neo4j\ONM\Mapping\Property;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use ProxyManager\Factory\LazyLoadingGhostFactory;
use ProxyManager\Proxy\LazyLoadingInterface;

class Hydrator
{
    protected $uow;
    protected $map;
    protected $registry;
    protected $accessor;
    protected $entities;
    protected $proxyFactory;

    public function __construct(
        UnitOfWork $uow,
        EntitySilo $entities,
        PropertyAccessor $accessor,
        LazyLoadingGhostFactory $proxyFactory
    ) {
        $this->uow = $uow;
        $this->map = $uow->getIdentityMap();
        $this->registry = $uow->getMetadataRegistry();
        $this->entities = $entities;
        $this->accessor = $accessor;
        $this->proxyFactory = $proxyFactory;
    }

    /**
     * Take the results for the given query an build a collection of appropriate entities
     *
     * @param array $results
     * @param Query $query
     *
     * @return \SplObjectStorage
     */
    public function hydrate(array $results, Query $query)
    {
        $entities = new \SplObjectStorage;
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

                        $this->entities->addInfo($entity, ['realId' => $node['id']]);
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

                        $this->entities->addInfo($entity, [
                            'realId' => $rel['id'],
                            'startNode' => $rel['startNode'],
                            'endNode' => $rel['endNode'],
                        ]);
                        break;
                    }
                }

                $entities->attach($entity);
            }
        }

        $entities->rewind();

        return $entities;
    }

    /**
     * Create an entity
     *
     * @param Metadata $meta
     * @param array $properties
     *
     * @return object
     */
    protected function createEntity(Metadata $meta, array $properties)
    {
        $class = $meta->getClass();
        $id = $properties[$meta->getId()->getProperty()];

        if ($this->entities->has($class, $id)) {
            return $this->entities->get($class, $id);
        }

        $entity = $this->proxyFactory->createProxy(
            $class,
            function(LazyLoadingInterface $proxy, $method, array $parameters, &$initializer) {
                $this->lazyLoad($proxy, $initializer);
            }
        );

        $data = [];

        foreach ($properties as $property => $value) {
            if (!$meta->hasProperty($property)) {
                continue;
            }

            $property = $meta->getProperty($property);
            $data[$property->getName()] = Types::getType($property->getType())
                ->convertToPHPValue($value, $property);
        }

        $this->entities->add($entity, $class, $id, ['properties' => $data]);

        return $entity;
    }

    /**
     * Lazy load an entity
     *
     * @param LazyLoadingInterface $proxy Entity proxy
     * @param Closure $initializer The closure used to load the given entity
     *
     * @return bool
     */
    protected function lazyLoad(LazyLoadingInterface $proxy, &$initializer) {
        $initializer = null;

        $info = $this->entities->getInfo($proxy);
        $class = $this->entities->getClass($proxy);
        $metadata = $this->registry->getMetadata($class);

        foreach ($metadata->getProperties() as $property) {
            if (!isset($info['properties'][$property->getName()])) {
                if ($property->getType() === 'relationship') {
                    $relationships = $this->getNodeRelationships(
                        $metadata,
                        $property,
                        $info
                    );

                    foreach ($relationships as $relationship) {
                        $this->accessor->setValue(
                            $proxy,
                            $property->getName(),
                            $relationship
                        );
                    }
                } else if (in_array($property->getType(), ['startNode', 'endNode'])) {
                    $node = $this->getRelationshipNode(
                        $property,
                        $info
                    );

                    $this->accessor->setValue(
                        $proxy,
                        $property->getName(),
                        $node
                    );
                }

                continue;
            }

            if ($metadata->getId()->getProperty() === $property->getName()) {
                $refl = new \ReflectionClass($metadata->getClass());
                $refl = $refl->getProperty($property->getName());
                $refl->setAccessible(true);
                $refl->setValue(
                    $proxy,
                    $info['properties'][$property->getName()]
                );
                $refl->setAccessible(false);
            } else {
                $this->accessor->setValue(
                    $proxy,
                    $property->getName(),
                    $info['properties'][$property->getName()]
                );
            }
        }

        return true;
    }

    /**
     * Find the relationships for the given node property
     *
     * @param NodeMetadata $metadata
     * @param Property $property
     * @param array $info
     *
     * @return array
     */
    protected function getNodeRelationships(NodeMetadata $metadata, Property $property, array $info)
    {
        $relationships = [];
        $relClass = $this->map->getClass($property->getOption('relationship'));

        foreach ($this->entities as $entity) {
            if (!$entity instanceof $relClass) {
                continue;
            }

            $relInfo = $this->entities->getInfo($entity);

            if (in_array($info['realId'], [$relInfo['startNode'], $relInfo['endNode']], true)) {
                $relationships[] = $entity;
            }
        }

        if (!empty($relationships)) {
            return $relationships;
        }

        $idProp = $metadata->getId()->getProperty();

        $qb = new QueryBuilder;
        $qb
            ->addExpr(
                $qb
                    ->expr()
                    ->matchNode(
                        'n',
                        $metadata->getClass(),
                        [
                            $idProp => $info['properties'][$idProp]
                        ]
                    )
                    ->relatedTo(
                        $qb
                            ->expr()
                            ->matchRelationship('r', $relClass)
                    )
            )
            ->toReturn('r');

        return $this->uow->execute($qb->getQuery());
    }

    /**
     * Find the nodes related to the relationship
     *
     * @param Property $property
     * @param array $info
     *
     * @return object
     */
    protected function getRelationshipNode(Property $property, array $info)
    {
        $nodeClass = $this->map->getClass($property->getOption('node'));

        foreach ($this->entities as $entity) {
            if (!$entity instanceof $nodeClass) {
                continue;
            }

            $nodeInfo = $this->entities->getInfo($entity);

            if ($nodeInfo['realId'] === $info[$property->getType()]) {
                return $entity;
            }
        }

        $query = new Query(sprintf(
            'MATCH (n:%s) WHERE id(n) = {where}.id RETURN n;',
            $nodeClass
        ));
        $query->addVariable('n', $nodeClass);
        $query->addParameters('where', ['id' => $info[$property->getType()]]);

        return $this->uow->execute($query)->current();
    }
}
