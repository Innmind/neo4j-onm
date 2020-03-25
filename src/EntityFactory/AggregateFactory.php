<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\EntityFactory;

use Innmind\Neo4j\ONM\{
    EntityFactory as EntityFactoryInterface,
    Identity,
    Metadata\Entity,
    Metadata\Aggregate,
    Metadata\Aggregate\Child,
    Metadata\Property,
    Exception\InvalidArgumentException,
};
use Innmind\Immutable\{
    Map,
    Set,
};
use function Innmind\Immutable\assertMap;
use Innmind\Reflection\{
    ReflectionClass,
    Instanciator\ConstructorLessInstanciator,
    InjectionStrategy\ReflectionStrategy,
};

final class AggregateFactory implements EntityFactoryInterface
{
    private ConstructorLessInstanciator $instanciator;
    private ReflectionStrategy $injectionStrategy;

    public function __construct()
    {
        $this->instanciator = new ConstructorLessInstanciator;
        $this->injectionStrategy = new ReflectionStrategy;
    }

    public function __invoke(Identity $identity, Entity $meta, Map $data): object
    {
        if (!$meta instanceof Aggregate) {
            throw new InvalidArgumentException;
        }

        assertMap('string', 'mixed', $data, 3);

        $reflection = $this
            ->reflection($meta->class()->toString())
            ->withProperty(
                $meta->identity()->property(),
                $identity,
            );

        $reflection = $meta
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
                            $data->get($name),
                        ),
                    );
                },
            );

        return $meta
            ->children()
            ->reduce(
                $reflection,
                function(ReflectionClass $carry, string $property, Child $meta) use ($data): ReflectionClass {
                    return $carry->withProperty(
                        $property,
                        $this->buildChild($meta, $data),
                    );
                },
            )
            ->build();
    }

    /**
     * @param Map<string, mixed> $data
     */
    private function buildChild(Child $meta, Map $data): object
    {
        $relationship = $meta->relationship();
        /** @var Map<string, mixed> */
        $data = $data->get($relationship->property());

        return $this->buildRelationship($meta, $data);
    }

    /**
     * @param Map<string, mixed> $data
     */
    private function buildRelationship(Child $meta, Map $data): object
    {
        $relationship = $meta->relationship();

        /** @psalm-suppress MixedArgument */
        return $relationship
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
                $this->reflection($relationship->class()->toString()),
                static function(ReflectionClass $carry, string $name, Property $property) use ($data): ReflectionClass {
                    return $carry->withProperty(
                        $name,
                        $property->type()->fromDatabase(
                            $data->get($name),
                        ),
                    );
                },
            )
            ->withProperty(
                $relationship->childProperty(),
                $this->buildValueObject(
                    $meta,
                    $data->get(
                        $relationship->childProperty(),
                    ),
                ),
            )
            ->build();
    }

    private function buildValueObject(Child $meta, Map $data): object
    {
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
                $this->reflection($meta->class()->toString()),
                static function(ReflectionClass $carry, string $name, Property $property) use ($data): ReflectionClass {
                    return $carry->withProperty(
                        $name,
                        $property->type()->fromDatabase(
                            $data->get($name),
                        ),
                    );
                },
            )
            ->build();
    }

    /**
     * @param class-string $class
     */
    private function reflection(string $class): ReflectionClass
    {
        return new ReflectionClass(
            $class,
            null,
            $this->injectionStrategy,
            $this->instanciator,
        );
    }
}
