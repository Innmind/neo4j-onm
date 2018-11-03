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

final class Aggregate extends AbstractEntity implements Entity
{
    private $labels;
    private $children;

     public function __construct(
        ClassName $class,
        Identity $id,
        SetInterface $labels,
        SetInterface $children
    ) {
        parent::__construct(
            $class,
            $id,
            new Repository(ConcreteRepository::class),
            new Factory(AggregateFactory::class)
        );

        if ((string) $labels->type() !== 'string') {
            throw new \TypeError('Argument 3 must be of type SetInterface<string>');
        }

        if ((string) $children->type() !== ValueObject::class) {
            throw new \TypeError(\sprintf(
                'Argument 4 must be of type SetInterface<%s>',
                ValueObject::class
            ));
        }

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
        $properties = $properties ?? Map::of('string', Type::class);
        $self = new self(
            $class,
            $identity,
            $labels,
            $children ?? Set::of(ValueObject::class)
        );

        return $properties->reduce(
            $self,
            static function(self $self, string $property, Type $type): self {
                return $self->withProperty($property, $type);
            }
        );
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
