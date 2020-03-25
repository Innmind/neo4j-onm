<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Result;

use Innmind\Neo4j\ONM\{
    Translation\EntityTranslator,
    Metadata\Entity,
    Metadata\Aggregate,
    Metadata\Aggregate\Child,
    Metadata\Property,
    Exception\InvalidArgumentException,
    Exception\DomainException,
    Exception\MoreThanOneRelationshipFound,
};
use Innmind\Neo4j\DBAL\{
    Result,
    Result\Node,
    Result\Relationship,
    Result\Row,
};
use Innmind\Immutable\{
    Map,
    Set,
    Str,
};

final class AggregateTranslator implements EntityTranslator
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(
        string $variable,
        Entity $meta,
        Result $result
    ): Set {
        if (Str::of($variable)->empty()) {
            throw new DomainException;
        }

        if (!$meta instanceof Aggregate) {
            throw new InvalidArgumentException;
        }

        /** @var Set<Map<string, mixed>> */
        return $result
            ->rows()
            ->filter(static function(Row $row) use ($variable) {
                return $row->column() === $variable;
            })
            ->reduce(
                Set::of(Map::class),
                function(Set $carry, Row $row) use ($meta, $result): Set {
                    /** @psalm-suppress PossiblyInvalidArrayAccess */
                    return $carry->add($this->translateNode(
                        $row->value()[$meta->identity()->property()],
                        $meta,
                        $result
                    ));
                }
            );
    }

    /**
     * @param mixed $identity
     *
     * @return Map<string, mixed>
     */
    private function translateNode(
        $identity,
        Aggregate $meta,
        Result $result
    ): Map {
        $node = $result
            ->nodes()
            ->filter(static function(int $id, Node $node) use ($identity, $meta) {
                $id = $meta->identity()->property();
                $properties = $node->properties();

                return $properties->contains($id) &&
                    $properties->get($id) === $identity;
            })
            ->values()
            ->first();
        /** @var Map<string, mixed> */
        $data = Map::of('string', 'mixed');
        $data = ($data)
            (
                $meta->identity()->property(),
                $node->properties()->get(
                    $meta->identity()->property()
                )
            );

        $data = $meta
            ->properties()
            ->filter(static function(string $name, Property $property) use ($node): bool {
                if (
                    $property->type()->isNullable() &&
                    !$node->properties()->contains($name)
                ) {
                    return false;
                }

                return true;
            })
            ->reduce(
                $data,
                static function(Map $carry, string $name) use ($node): Map {
                    return $carry->put(
                        $name,
                        $node->properties()->get($name)
                    );
                }
            );

        try {
            /** @var Map<string, mixed> */
            return $meta
                ->children()
                ->reduce(
                    $data,
                    function(Map $carry, string $name, Child $meta) use ($node, $result): Map {
                        return $carry->put(
                            $name,
                            $this->translateChild($meta, $result, $node)
                        );
                    }
                );
        } catch (MoreThanOneRelationshipFound $e) {
            throw $e->on($meta);
        }
    }

    private function translateChild(
        Child $meta,
        Result $result,
        Node $node
    ): Map {
        $relMeta = $meta->relationship();
        $relationships = $result
            ->relationships()
            ->filter(static function(
                int $id,
                Relationship $relationship
            ) use (
                $node,
                $relMeta
            ): bool {
                return $relationship->type()->value() === (string) $relMeta->type() &&
                    $relationship->endNode()->value() === $node->id()->value();
            })
            ->values();

        if ($relationships->size() > 1) {
            throw MoreThanOneRelationshipFound::for($meta);
        }

        return $this->translateRelationship(
            $meta,
            $result,
            $relationships->first()
        );
    }

    private function translateRelationship(
        Child $meta,
        Result $result,
        Relationship $relationship
    ): Map {
        /**
         * @psalm-suppress InvalidArgument
         * @psalm-suppress InvalidScalarArgument
         */
        return $meta
            ->relationship()
            ->properties()
            ->filter(static function(string $name, Property $property) use ($relationship): bool {
                if (
                    $property->type()->isNullable() &&
                    !$relationship->properties()->contains($name)
                ) {
                    return false;
                }

                return true;
            })
            ->reduce(
                Map::of('string', 'mixed'),
                static function(Map $carry, string $name) use ($relationship): Map {
                    return $carry->put(
                        $name,
                        $relationship->properties()->get($name)
                    );
                }
            )
            ->put(
                $meta->relationship()->childProperty(),
                $this->translateValueObject(
                    $meta,
                    $result,
                    $relationship
                )
            );
    }

    private function translateValueObject(
        Child $meta,
        Result $result,
        Relationship $relationship
    ): Map {
        $node = $result
            ->nodes()
            ->get($relationship->startNode()->value());

        return $meta
            ->properties()
            ->filter(static function(string $name, Property $property) use ($node): bool {
                if (
                    $property->type()->isNullable() &&
                    !$node->properties()->contains($name)
                ) {
                    return false;
                }

                return true;
            })
            ->reduce(
                Map::of('string', 'mixed'),
                static function(Map $carry, string $name) use ($node): Map {
                    return $carry->put(
                        $name,
                        $node->properties()->get($name)
                    );
                }
            );
    }
}
