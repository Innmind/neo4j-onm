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
    MapInterface,
    Map,
    SetInterface,
    Set,
};

final class Aggregate implements Entity
{
    private $class;
    private $identity;
    private $repository;
    private $factory;
    private $properties;
    private $labels;
    private $children;

    public function __construct(
        ClassName $class,
        Identity $identity,
        SetInterface $labels,
        SetInterface $properties,
        SetInterface $children
    ) {
        if ((string) $labels->type() !== 'string') {
            throw new \TypeError('Argument 3 must be of type SetInterface<string>');
        }

        if ((string) $properties->type() !== Property::class) {
            throw new \TypeError(\sprintf(
                'Argument 4 must be of type SetInterface<%s>',
                Type::class
            ));
        }

        if ((string) $children->type() !== Child::class) {
            throw new \TypeError(\sprintf(
                'Argument 5 must be of type SetInterface<%s>',
                Child::class
            ));
        }

        $this->class = $class;
        $this->identity = $identity;
        $this->repository = new Repository(ConcreteRepository::class);
        $this->factory = new Factory(AggregateFactory::class);
        $this->properties = $properties->reduce(
            Map::of('string', Property::class),
            static function(MapInterface $properties, Property $property): MapInterface {
                return $properties->put($property->name(), $property);
            }
        );
        $this->labels = $labels;
        $this->children = $children->reduce(
            Map::of('string', Child::class),
            static function(MapInterface $children, Child $child): MapInterface {
                return $children->put(
                    $child->relationship()->property(),
                    $child
                );
            }
        );
    }

    /**
     * @param SetInterface<string> $labels
     * @param MapInterface<string, Type> $properties
     * @param SetInterface<Child> $children
     */
    public static function of(
        ClassName $class,
        Identity $identity,
        SetInterface $labels,
        MapInterface $properties = null,
        SetInterface $children = null
    ): self {
        return new self(
            $class,
            $identity,
            $labels,
            ($properties ?? Map::of('string', Type::class))->reduce(
                Set::of(Property::class),
                static function(SetInterface $properties, string $name, Type $type): SetInterface {
                    return $properties->add(new Property($name, $type));
                }
            ),
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
    public function properties(): MapInterface
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
     * @return SetInterface<string>
     */
    public function labels(): SetInterface
    {
        return $this->labels;
    }

    /**
     * @return MapInterface<string, Child>
     */
    public function children(): MapInterface
    {
        return $this->children;
    }
}
