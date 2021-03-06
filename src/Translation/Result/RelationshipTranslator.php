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
    Map,
    Set,
};

final class RelationshipTranslator implements EntityTranslator
{
    public function __invoke(
        string $variable,
        Entity $meta,
        Result $result
    ): Set {
        if (empty($variable)) {
            throw new DomainException;
        }

        if (!$meta instanceof Relationship) {
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
                    return ($carry)(
                        $this->translateRelationship(
                            $row->value()[$meta->identity()->property()],
                            $meta,
                            $result,
                        ),
                    );
                },
            );
    }

    /**
     * @param mixed $identity
     *
     * @return Map<string, mixed>
     */
    private function translateRelationship(
        $identity,
        Relationship $meta,
        Result $result
    ): Map {
        $relationship = $result
            ->relationships()
            ->filter(static function(int $id, DBALRelationship $relationship) use ($identity, $meta): bool {
                $id = $meta->identity()->property();
                $properties = $relationship->properties();

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
                $relationship->properties()->get(
                    $meta->identity()->property(),
                ),
            )
            (
                $meta->startNode()->property(),
                $result
                    ->nodes()
                    ->get($relationship->startNode()->value())
                    ->properties()
                    ->get($meta->startNode()->target()),
            )
            (
                $meta->endNode()->property(),
                $result
                    ->nodes()
                    ->get($relationship->endNode()->value())
                    ->properties()
                    ->get($meta->endNode()->target()),
            );

        /** @var Map<string, mixed> */
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
                static function(Map $carry, string $name) use ($relationship): Map {
                    return ($carry)(
                        $name,
                        $relationship->properties()->get($name),
                    );
                },
            );
    }
}
