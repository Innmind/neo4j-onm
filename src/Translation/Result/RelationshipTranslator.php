<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Result;

use Innmind\Neo4j\ONM\{
    Translation\EntityTranslatorInterface,
    Metadata\EntityInterface,
    Metadata\Relationship,
    Metadata\Property,
    Exception\InvalidArgumentException
};
use Innmind\Neo4j\DBAL\{
    Result,
    Result\Relationship as DBALRelationship,
    Result\Row
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    SetInterface,
    Set
};

final class RelationshipTranslator implements EntityTranslatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function translate(
        string $variable,
        EntityInterface $meta,
        Result $result
    ): SetInterface {
        if (empty($variable) || !$meta instanceof Relationship) {
            throw new InvalidArgumentException;
        }

        return $result
            ->rows()
            ->filter(function(Row $row) use ($variable) {
                return $row->column() === $variable;
            })
            ->reduce(
                new Set(MapInterface::class),
                function(Set $carry, Row $row) use ($meta, $result): Set {
                    return $carry->add(
                        $this->translateRelationship(
                            $row->value()[$meta->identity()->property()],
                            $meta,
                            $result
                        )
                    );
                }
            );
    }

    private function translateRelationship(
        $identity,
        EntityInterface $meta,
        Result $result
    ): MapInterface {
        $relationship = $result
            ->relationships()
            ->filter(function(int $id, DBALRelationship $relationship) use ($identity, $meta) {
                $id = $meta->identity()->property();
                $properties = $relationship->properties();

                return $properties->contains($id) &&
                    $properties->get($id) === $identity;
            })
            ->current();
        $data = (new Map('string', 'mixed'))
            ->put(
                $meta->identity()->property(),
                $relationship->properties()->get(
                    $meta->identity()->property()
                )
            )
            ->put(
                $meta->startNode()->property(),
                $result
                    ->nodes()
                    ->get($relationship->startNode()->value())
                    ->properties()
                    ->get($meta->startNode()->target())
            )
            ->put(
                $meta->endNode()->property(),
                $result
                    ->nodes()
                    ->get($relationship->endNode()->value())
                    ->properties()
                    ->get($meta->endNode()->target())
            );

        return $meta
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
                $data,
                function(Map $carry, string $name, Property $property) use ($relationship): Map {
                    return $carry->put(
                        $name,
                        $relationship->properties()->get($name)
                    );
                }
            );
    }
}
