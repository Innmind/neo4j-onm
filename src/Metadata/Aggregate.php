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
        if ((string) $labels->type() !== 'string') {
            throw new \TypeError('Argument 3 must be of type Set<string>');
        }

        if ((string) $properties->type() !== Property::class) {
            throw new \TypeError(\sprintf(
                'Argument 4 must be of type Set<%s>',
                Type::class
            ));
        }

        if ((string) $children->type() !== Child::class) {
            throw new \TypeError(\sprintf(
                'Argument 5 must be of type Set<%s>',
                Child::class
            ));
        }

        $this->class = $class;
        $this->identity = $identity;
        $this->repository = new Repository(ConcreteRepository::class);
        $this->factory = new Factory(AggregateFactory::class);
        /** @var Map<string, Property> */
        $this->properties = $properties->reduce(
            Map::of('string', Property::class),
            static function(Map $properties, Property $property): Map {
                return $properties->put($property->name(), $property);
            }
        );
        $this->labels = $labels;
        /** @var Map<string, Child> */
        $this->children = $children->reduce(
            Map::of('string', Child::class),
            static function(Map $children, Child $child): Map {
                return $children->put(
                    $child->relationship()->property(),
                    $child
                );
            }
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
            $children ?? Set::of(Child::class)
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
