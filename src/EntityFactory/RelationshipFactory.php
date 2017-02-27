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
use Innmind\Immutable\MapInterface;
use Innmind\Reflection\{
    ReflectionClass,
    InstanciatorInterface,
    InjectionStrategyInterface
};

final class RelationshipFactory implements EntityFactoryInterface
{
    private $generators;
    private $instanciator;
    private $injectionStrategy;

    public function __construct(
        Generators $generators,
        InstanciatorInterface $instanciator = null,
        InjectionStrategyInterface $injectionStrategy = null
    ) {
        $this->generators = $generators;
        $this->instanciator = $instanciator;
        $this->injectionStrategy = $injectionStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function make(
        IdentityInterface $identity,
        EntityInterface $meta,
        MapInterface $data
    ) {
        if (
            !$meta instanceof Relationship ||
            (string) $data->keyType() !== 'string' ||
            (string) $data->valueType() !== 'mixed'
        ) {
            throw new InvalidArgumentException;
        }

        $reflection = (new ReflectionClass(
            (string) $meta->class(),
            null,
            $this->injectionStrategy,
            $this->instanciator
        ))
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

        return $meta
            ->properties()
            ->filter(function(string $name, Property $property) use ($data): bool {
                if (
                    $property->type()->isNullable() &&
                    !$data->contains($name)
                ) {
                    return false;
                }

                return true;
            })
            ->reduce(
                $reflection,
                function(ReflectionClass $carry, string $name, Property $property) use ($data): ReflectionClass {
                    return $carry->withProperty(
                        $name,
                        $property->type()->fromDatabase(
                            $data->get($name)
                        )
                    );
                }
            )
            ->build();
    }
}
