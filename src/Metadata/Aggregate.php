<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    Metadata\Aggregate\Child,
    EntityFactory\AggregateFactory,
    Repository\Repository as ConcreteRepository,
    Type,
};
use Innmind\Immutable\{
    Map,
    Set,
};
use function Innmind\Immutable\assertSet;

final class Aggregate implements Entity
{
    private ClassName $class;
    private Identity $identity;
    private Repository $repository;
    private Factory $factory;
    /** @var Map<string, Property> */
    private Map $properties;
    /** @var Set<string> */
    private Set $labels;
    /** @var Map<string, Child> */
    private Map $children;

    /**
     * @param Set<string> $labels
     * @param Set<Property> $properties
     * @param Set<Child> $children
     */
    public function __construct(
        ClassName $class,
        Identity $identity,
        Set $labels,
        Set $properties,
        Set $children
    ) {
        assertSet('string', $labels, 3);
        assertSet(Property::class, $properties, 4);
        assertSet(Child::class, $children, 5);

        $this->class = $class;
        $this->identity = $identity;
        $this->repository = new Repository(ConcreteRepository::class);
        $this->factory = new Factory(AggregateFactory::class);
        /** @var Map<string, Property> */
        $this->properties = $properties->toMapOf(
            'string',
            Property::class,
            static function(Property $property): \Generator {
                yield $property->name() => $property;
            },
        );
        $this->labels = $labels;
        /** @var Map<string, Child> */
        $this->children = $children->toMapOf(
            'string',
            Child::class,
            static function(Child $child): \Generator {
                yield $child->relationship()->property() => $child;
            },
        );
    }

    /**
     * @param Set<string> $labels
     * @param Map<string, Type>|null $properties
     * @param Set<Child>|null $children
     */
    public static function of(
        ClassName $class,
        Identity $identity,
        Set $labels,
        Map $properties = null,
        Set $children = null
    ): self {
        /** @var Map<string, Type> */
        $properties ??= Map::of('string', Type::class);
        /** @var Set<Property> */
        $properties = $properties->toSetOf(
            Property::class,
            static fn(string $property, Type $type): \Generator => yield new Property($property, $type),
        );

        return new self(
            $class,
            $identity,
            $labels,
            $properties,
            $children ?? Set::of(Child::class),
        );
    }

    public function identity(): Identity
    {
        return $this->identity;
    }

    public function repository(): Repository
    {
        return $this->repository;
    }

    public function factory(): Factory
    {
        return $this->factory;
    }

    public function properties(): Map
    {
        return $this->properties;
    }

    public function class(): ClassName
    {
        return $this->class;
    }

    /**
     * @return Set<string>
     */
    public function labels(): Set
    {
        return $this->labels;
    }

    /**
     * @return Map<string, Child>
     */
    public function children(): Map
    {
        return $this->children;
    }
}
