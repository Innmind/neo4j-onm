<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
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
        MapInterface $properties,
        SetInterface $children
    ) {
        if ((string) $labels->type() !== 'string') {
            throw new \TypeError('Argument 3 must be of type SetInterface<string>');
        }

        if (
            (string) $properties->keyType() !== 'string' ||
            (string) $properties->ValueType() !== Type::class
        ) {
            throw new \TypeError(\sprintf(
                'Argument 4 must be of type MapInterface<string, %s>',
                Type::class
            ));
        }

        if ((string) $children->type() !== ValueObject::class) {
            throw new \TypeError(\sprintf(
                'Argument 5 must be of type SetInterface<%s>',
                ValueObject::class
            ));
        }

        $this->class = $class;
        $this->identity = $identity;
        $this->repository = new Repository(ConcreteRepository::class);
        $this->factory = new Factory(AggregateFactory::class);
        $this->properties = $properties->reduce(
            Map::of('string', Property::class),
            static function(MapInterface $properties, string $name, Type $type): MapInterface {
                return $properties->put($name, new Property($name, $type));
            }
        );
        $this->labels = $labels;
        $this->children = $children->reduce(
            Map::of('string', ValueObject::class),
            static function(MapInterface $children, ValueObject $child): MapInterface {
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
     * @param SetInterface<ValueObject> $children
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
            $properties ?? Map::of('string', Type::class),
            $children ?? Set::of(ValueObject::class)
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
     * @return MapInterface<string, ValueObject>
     */
    public function children(): MapInterface
    {
        return $this->children;
    }
}
