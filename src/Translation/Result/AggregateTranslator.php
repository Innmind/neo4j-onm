<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Result;

use Innmind\Neo4j\ONM\{
    Translation\EntityTranslator,
    Metadata\Entity,
    Metadata\Aggregate,
    Metadata\Child,
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
    MapInterface,
    Map,
    SetInterface,
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
    ): SetInterface {
        if (Str::of($variable)->empty()) {
            throw new DomainException;
        }

        if (!$meta instanceof Aggregate) {
            throw new InvalidArgumentException;
        }

        return $result
            ->rows()
            ->filter(static function(Row $row) use ($variable) {
                return $row->column() === $variable;
            })
            ->reduce(
                new Set(MapInterface::class),
                function(SetInterface $carry, Row $row) use ($meta, $result): SetInterface {
                    return $carry->add($this->translateNode(
                        $row->value()[$meta->identity()->property()],
                        $meta,
                        $result
                    ));
                }
            );
    }

    private function translateNode(
        $identity,
        Entity $meta,
        Result $result
    ): MapInterface {
        $node = $result
            ->nodes()
            ->filter(static function(int $id, Node $node) use ($identity, $meta) {
                $id = $meta->identity()->property();
                $properties = $node->properties();

                return $properties->contains($id) &&
                    $properties->get($id) === $identity;
            })
            ->current();
        $data = Map::of('string', 'mixed')
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
                static function(MapInterface $carry, string $name) use ($node): MapInterface {
                    return $carry->put(
                        $name,
                        $node->properties()->get($name)
                    );
                }
            );

        try {
            return $meta
                ->children()
                ->reduce(
                    $data,
                    function(MapInterface $carry, string $name, Child $meta) use ($node, $result): MapInterface {
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
    ): MapInterface {
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
                return (string) $relationship->type() === (string) $relMeta->type() &&
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
    ): MapInterface {
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
                new Map('string', 'mixed'),
                static function(MapInterface $carry, string $name) use ($relationship): MapInterface {
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
    ): MapInterface {
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
                new Map('string', 'mixed'),
                static function(MapInterface $carry, string $name) use ($node): MapInterface {
                    return $carry->put(
                        $name,
                        $node->properties()->get($name)
                    );
                }
            );
    }
}
