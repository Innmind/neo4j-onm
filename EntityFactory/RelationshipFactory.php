<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\EntityFactory;

use Innmind\Neo4j\ONM\{
    EntityFactoryInterface,
    IdentityInterface,
    Metadata\EntityInterface,
    Metadata\Relationship,
    Metadata\Property,
    Identity\Generators,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\CollectionInterface;
use Innmind\Reflection\ReflectionClass;

class RelationshipFactory implements EntityFactoryInterface
{
    private $generators;

    public function __construct(Generators $generators)
    {
        $this->generators = $generators;
    }

    /**
     * {@inheritdoc}
     */
    public function make(
        IdentityInterface $identity,
        EntityInterface $meta,
        CollectionInterface $data
    ) {
        if (!$meta instanceof Relationship) {
            throw new InvalidArgumentException;
        }

        $reflection = (new ReflectionClass((string) $meta->class()))
            ->withProperty(
                $meta->identity()->property(),
                $identity
            )
            ->withProperty(
                $meta->startNode()->property(),
                $this
                    ->generators
                    ->get($meta->startNode()->type())
                    ->for(
                        $data->get($meta->startNode()->property())
                    )
            )
            ->withProperty(
                $meta->endNode()->property(),
                $this
                    ->generators
                    ->get($meta->endNode()->type())
                    ->for(
                        $data->get($meta->endNode()->property())
                    )
            );

        $meta
            ->properties()
            ->foreach(function(
                string $name,
                Property $property
            ) use (
                &$reflection,
                $data
            ) {
                if (
                    $property->type()->isNullable() &&
                    !$data->hasKey($name)
                ) {
                    return;
                }

                $reflection = $reflection->withProperty(
                    $name,
                    $property->type()->fromDatabase(
                        $data->get($name)
                    )
                );
            });

        return $reflection->buildObject();
    }
}
