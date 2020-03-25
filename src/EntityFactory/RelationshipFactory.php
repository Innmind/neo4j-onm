<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\EntityFactory;

use Innmind\Neo4j\ONM\{
    EntityFactory as EntityFactoryInterface,
    Identity,
    Metadata\Entity,
    Metadata\Relationship,
    Metadata\Property,
    Identity\Generators,
    Exception\InvalidArgumentException,
};
use Innmind\Immutable\Map;
use Innmind\Reflection\{
    ReflectionClass,
    Instanciator\ConstructorLessInstanciator,
    InjectionStrategy\ReflectionStrategy,
};

final class RelationshipFactory implements EntityFactoryInterface
{
    private Generators $generators;
    private ConstructorLessInstanciator $instanciator;
    private ReflectionStrategy $injectionStrategy;

    public function __construct(Generators $generators)
    {
        $this->generators = $generators;
        $this->instanciator = new ConstructorLessInstanciator;
        $this->injectionStrategy = new ReflectionStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(
        Identity $identity,
        Entity $meta,
        Map $data
    ): object {
        if (!$meta instanceof Relationship) {
            throw new InvalidArgumentException;
        }

        if (
            (string) $data->keyType() !== 'string' ||
            (string) $data->valueType() !== 'mixed'
        ) {
            throw new \TypeError('Argument 3 must be of type Map<string, mixed>');
        }

        $reflection = ReflectionClass::of(
            (string) $meta->class(),
            null,
            $this->injectionStrategy,
            $this->instanciator
        )
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
            ->filter(static function(string $name, Property $property) use ($data): bool {
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
                static function(ReflectionClass $carry, string $name, Property $property) use ($data): ReflectionClass {
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
