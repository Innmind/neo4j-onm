<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\EntityFactory;

use Innmind\Neo4j\ONM\{
    EntityFactoryInterface,
    IdentityInterface,
    Metadata\EntityInterface,
    Metadata\Aggregate,
    Metadata\Property,
    Metadata\ValueObject,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\{
    CollectionInterface,
    Set
};
use Innmind\Reflection\{
    ReflectionClass,
    InstanciatorInterface,
    InjectionStrategy\InjectionStrategiesInterface
};

class AggregateFactory implements EntityFactoryInterface
{
    private $instanciator;
    private $injectionStrategies;

    public function __construct(
        InstanciatorInterface $instanciator = null,
        InjectionStrategiesInterface $injectionStrategies = null
    ) {
        $this->instanciator = $instanciator;
        $this->injectionStrategies = $injectionStrategies;
    }

    /**
     * {@inheritdoc}
     */
    public function make(
        IdentityInterface $identity,
        EntityInterface $meta,
        CollectionInterface $data
    ) {
        if (!$meta instanceof Aggregate) {
            throw new InvalidArgumentException;
        }

        $reflection = $this
            ->reflection((string) $meta->class())
            ->withProperty(
                $meta->identity()->property(),
                $identity
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

        $meta
            ->children()
            ->foreach(function(
                string $property,
                ValueObject $meta
            ) use (
                &$reflection,
                $data
            ) {
                $reflection = $reflection->withProperty(
                    $property,
                    $this->buildChild($meta, $data)
                );
            });

        return $reflection->buildObject();
    }

    private function buildChild(ValueObject $meta, CollectionInterface $data)
    {
        $relationship = $meta->relationship();
        $data = $data->get($relationship->property());

        return $this->buildRelationship($meta, $data);
    }

    private function buildRelationship(
        ValueObject $meta,
        CollectionInterface $data
    ) {
        $relationship = $meta->relationship();
        $reflection = $this->reflection((string) $relationship->class());

        $relationship
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

        $reflection = $reflection->withProperty(
            $relationship->childProperty(),
            $this->buildValueObject(
                $meta,
                $data->get(
                    $relationship->childProperty()
                )
            )
        );

        return $reflection->buildObject();
    }

    private function buildValueObject(
        ValueObject $meta,
        CollectionInterface $data
    ) {
        $reflection = $this->reflection((string) $meta->class());

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

    private function reflection(string $class): ReflectionClass
    {
        return new ReflectionClass(
            $class,
            null,
            $this->injectionStrategies,
            $this->instanciator
        );
    }
}
