<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Immutable\Collection;
use Innmind\Immutable\TypedCollection;
use Innmind\Immutable\CollectionInterface;
use Innmind\Immutable\TypedCollectionInterface;

class AggregateRoot extends Entity implements EntityInterface
{
    private $labels;
    private $children;

     public function __construct(
        ClassName $class,
        Identity $id,
        Repository $repository,
        Factory $factory,
        Alias $alias,
        array $labels
    ) {
        parent::__construct($class, $id, $repository, $factory, $alias);

        $this->labels = new Collection($labels);
        $this->children = new TypedCollection(ValueObject::class, []);
    }

    public function labels(): CollectionInterface
    {
        return $this->labels;
    }

    public function children(): TypedCollectionInterface
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
        $aggregate->children = $this->children->set(
            (string) $child->relationship()->property(),
            $child
        );

        return $aggregate;
    }
}
