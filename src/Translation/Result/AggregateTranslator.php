<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Result;

use Innmind\Neo4j\ONM\{
    Translation\EntityTranslatorInterface,
    Metadata\EntityInterface,
    Metadata\Aggregate,
    Metadata\ValueObject,
    Metadata\Property,
    Exception\InvalidArgumentException,
    Exception\MoreThanOneRelationshipFoundException
};
use Innmind\Neo4j\DBAL\{
    ResultInterface,
    Result\NodeInterface,
    Result\RelationshipInterface,
    Result\RowInterface
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    SetInterface,
    Set
};

class AggregateTranslator implements EntityTranslatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function translate(
        string $variable,
        EntityInterface $meta,
        ResultInterface $result
    ): SetInterface {
        if (!$meta instanceof Aggregate) {
            throw new InvalidArgumentException;
        }

        return $result
            ->rows()
            ->filter(function(RowInterface $row) use ($variable) {
                return $row->column() === $variable;
            })
            ->reduce(
                new Set(MapInterface::class),
                function(Set $carry, RowInterface $row) use ($meta, $result): Set {
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
        EntityInterface $meta,
        ResultInterface $result
    ): MapInterface {
        $node = $result
            ->nodes()
            ->filter(function(int $id, NodeInterface $node) use ($identity, $meta) {
                $id = $meta->identity()->property();
                $properties = $node->properties();

                return $properties->contains($id) &&
                    $properties->get($id) === $identity;
            })
            ->current();
        $data = (new Map('string', 'mixed'))
            ->put(
                $meta->identity()->property(),
                $node->properties()->get(
                    $meta->identity()->property()
                )
            );

        $data = $meta
            ->properties()
            ->filter(function(string $name, Property $property) use ($node): bool {
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
                function(Map $carry, string $name, Property $property) use ($node): Map {
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
                    function(Map $carry, string $name, ValueObject $meta) use ($node, $result): Map {
                        return $carry->put(
                            $name,
                            $this->translateChild($meta, $result, $node)
                        );
                    }
                );
        } catch (MoreThanOneRelationshipFoundException $e) {
            throw $e->on($meta);
        }
    }

    private function translateChild(
        ValueObject $meta,
        ResultInterface $result,
        NodeInterface $node
    ): MapInterface {
        $relMeta = $meta->relationship();
        $relationships = $result
            ->relationships()
            ->filter(function(
                int $id,
                RelationshipInterface $relationship
            ) use (
                $node,
                $relMeta
            ) {
                return (string) $relationship->type() === (string) $relMeta->type() &&
                    $relationship->endNode()->value() === $node->id()->value();
            })
            ->values();

        if ($relationships->size() > 1) {
            throw MoreThanOneRelationshipFoundException::for($meta);
        }

        return $this->translateRelationship(
            $meta,
            $result,
            $relationships->first()
        );
    }

    private function translateRelationship(
        ValueObject $meta,
        ResultInterface $result,
        RelationshipInterface $relationship
    ): MapInterface {
        return $meta
            ->relationship()
            ->properties()
            ->filter(function(string $name, Property $property) use ($relationship): bool {
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
                function(Map $carry, string $name, Property $property) use ($relationship): Map {
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
        ValueObject $meta,
        ResultInterface $result,
        RelationshipInterface $relationship
    ): MapInterface {
        $node = $result
            ->nodes()
            ->get(
                $relationship->startNode()->value()
            );

        return $meta
            ->properties()
            ->filter(function(string $name, Property $property) use ($node): bool {
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
                function(Map $carry, string $name, Property $property) use ($node): Map {
                    return $carry->put(
                        $name,
                        $node->properties()->get($name)
                    );
                }
            );
    }
}
