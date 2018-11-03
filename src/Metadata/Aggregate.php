<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    EntityFactory\AggregateFactory,
    Repository\Repository as ConcreteRepository,
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
        array $labels
    ) {
        parent::__construct(
            $class,
            $id,
            new Repository(ConcreteRepository::class),
            new Factory(AggregateFactory::class)
        );

        $this->labels = Set::of('string', ...$labels);
        $this->children = new Map('string', ValueObject::class);
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

    /**
     * Add the given children
     *
     * @param ValueObject $child
     *
     * @return self
     */
    public function withChild(ValueObject $child): self
    {
        $aggregate = clone $this;
        $aggregate->children = $this->children->put(
            $child->relationship()->property(),
            $child
        );

        return $aggregate;
    }
}
