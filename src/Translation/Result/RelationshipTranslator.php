<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Result;

use Innmind\Neo4j\ONM\{
    Translation\EntityTranslator,
    Metadata\Entity,
    Metadata\Relationship,
    Metadata\Property,
    Exception\InvalidArgumentException,
    Exception\DomainException,
};
use Innmind\Neo4j\DBAL\{
    Result,
    Result\Relationship as DBALRelationship,
    Result\Row,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    SetInterface,
    Set,
};

final class RelationshipTranslator implements EntityTranslator
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(
        string $variable,
        Entity $meta,
        Result $result
    ): SetInterface {
        if (empty($variable)) {
            throw new DomainException;
        }

        if (!$meta instanceof Relationship) {
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
        Entity $meta,
        Result $result
    ): MapInterface {
        $relationship = $result
            ->relationships()
            ->filter(static function(int $id, DBALRelationship $relationship) use ($identity, $meta): bool {
                $id = $meta->identity()->property();
                $properties = $relationship->properties();

                return $properties->contains($id) &&
                    $properties->get($id) === $identity;
            })
            ->current();
        $data = Map::of('string', 'mixed')
            (
                $meta->identity()->property(),
                $relationship->properties()->get(
                    $meta->identity()->property()
                )
            )
            (
                $meta->startNode()->property(),
                $result
                    ->nodes()
                    ->get($relationship->startNode()->value())
                    ->properties()
                    ->get($meta->startNode()->target())
            )
            (
                $meta->endNode()->property(),
                $result
                    ->nodes()
                    ->get($relationship->endNode()->value())
                    ->properties()
                    ->get($meta->endNode()->target())
            );

        return $meta
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
                $data,
                static function(MapInterface $carry, string $name, Property $property) use ($relationship): MapInterface {
                    return $carry->put(
                        $name,
                        $relationship->properties()->get($name)
                    );
                }
            );
    }
}
