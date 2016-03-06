<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\EntityFactory;

use Innmind\Neo4j\ONM\{
    EntityFactoryInterface,
    IdentityInterface,
    Metadata\EntityInterface,
    Metadata\AggregateRoot,
    Metadata\Property,
    Metadata\ValueObject,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\{
    CollectionInterface,
    Set
};
use Innmind\Reflection\ReflectionClass;

class AggregateRootFactory implements EntityFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function make(
        IdentityInterface $identity,
        EntityInterface $meta,
        CollectionInterface $data
    ) {
        if (!$meta instanceof AggregateRoot) {
            throw new InvalidArgumentException;
        }

        $reflection = (new ReflectionClass((string) $meta->class()))
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

        if ($relationship->isCollection()) {
            $set = new Set((string) $relationship->class());

            $data->each(function(
                $key,
                CollectionInterface $value
            ) use (
                &$set,
                $meta
            ) {
                $set = $set->add(
                    $this->buildRelationship($meta, $value)
                );
            });

            return $set;
        }

        return $this->buildRelationship($relationship, $data);
    }

    private function buildRelationship(
        ValueObject $meta,
        CollectionInterface $data
    ) {
        $relationship = $meta->relationship();
        $reflection = new ReflectionClass((string) $relationship->class());

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
        $reflection = new ReflectionClass((string) $meta->class());

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
