<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    EntityFactory\RelationshipFactory,
    Repository\Repository as ConcreteRepository,
    Type,
};
use Innmind\Immutable\{
    Map,
    Set,
};

final class Relationship implements Entity
{
    private ClassName $class;
    private Identity $identity;
    private Repository $repository;
    private Factory $factory;
    private RelationshipType $type;
    private RelationshipEdge $startNode;
    private RelationshipEdge $endNode;
    private Map $properties;

    public function __construct(
        ClassName $class,
        Identity $identity,
        RelationshipType $type,
        RelationshipEdge $startNode,
        RelationshipEdge $endNode,
        Set $properties
    ) {
        if ((string) $properties->type() !== Property::class) {
            throw new \TypeError(\sprintf(
                'Argument 6 must be of type Set<%s>',
                Property::class
            ));
        }

        $this->class = $class;
        $this->identity = $identity;
        $this->repository = new Repository(ConcreteRepository::class);
        $this->factory = new Factory(RelationshipFactory::class);
        $this->type = $type;
        $this->startNode = $startNode;
        $this->endNode = $endNode;
        $this->properties = $properties->reduce(
            Map::of('string', Property::class),
            static function(Map $properties, Property $property): Map {
                return $properties->put($property->name(), $property);
            }
        );
    }

    public static function of(
        ClassName $class,
        Identity $identity,
        RelationshipType $type,
        RelationshipEdge $startNode,
        RelationshipEdge $endNode,
        Map $properties = null
    ): self {
        return new self(
            $class,
            $identity,
            $type,
            $startNode,
            $endNode,
            ($properties ?? Map::of('string', Type::class))->reduce(
                Set::of(Property::class),
                static function(Set $properties, string $property, Type $type): Set {
                    return $properties->add(new Property($property, $type));
                }
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function identity(): Identity
    {
        return $this->identity;
    }

    /**
     * {@inheritdoc}
     */
    public function repository(): Repository
    {
        return $this->repository;
    }

    /**
     * {@inheritdoc}
     */
    public function factory(): Factory
    {
        return $this->factory;
    }

    /**
     * {@inheritdoc}
     */
    public function properties(): Map
    {
        return $this->properties;
    }

    /**
     * {@inheritdoc}
     */
    public function class(): ClassName
    {
        return $this->class;
    }

    public function type(): RelationshipType
    {
        return $this->type;
    }

    public function startNode(): RelationshipEdge
    {
        return $this->startNode;
    }

    public function endNode(): RelationshipEdge
    {
        return $this->endNode;
    }
}
