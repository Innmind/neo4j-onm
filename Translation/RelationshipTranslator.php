<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation;

use Innmind\Neo4j\ONM\{
    Metadata\EntityInterface,
    Metadata\Relationship,
    Metadata\Property,
    Exception\InvalidArgumentException
};
use Innmind\Neo4j\DBAL\{
    ResultInterface,
    Result\RelationshipInterface
};
use Innmind\Immutable\{
    CollectionInterface,
    Collection
};

class RelationshipTranslator implements EntityTranslatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function translate(
        string $variable,
        EntityInterface $meta,
        ResultInterface $result
    ): CollectionInterface {
        if (!$meta instanceof Relationship) {
            throw new InvalidArgumentException;
        }

        $row = $result->rows()->get($variable);
        $relationship = $result
            ->relationships()
            ->filter(function(RelationshipInterface $relationship) use ($row, $meta) {
                $id = $meta->identity()->property();
                $properties = $relationship->properties();

                return $properties->hasKey($id) &&
                    $properties->get($id) === $row->value()[$id];
            })
            ->first();
        $data = (new Collection([]))
            ->set(
                $meta->identity()->property(),
                $relationship->properties()->get(
                    $meta->identity()->property()
                )
            )
            ->set(
                $meta->startNode()->property(),
                $result
                    ->nodes()
                    ->get($relationship->startNode()->value())
                    ->properties()
                    ->get($meta->startNode()->target())
            )
            ->set(
                $meta->endNode()->property(),
                $result
                    ->nodes()
                    ->get($relationship->endNode()->value())
                    ->properties()
                    ->get($meta->endNode()->target())
            );

        $meta
            ->properties()
            ->foreach(function(
                string $name,
                Property $property
            ) use (
                &$data,
                $relationship
            ) {
                if (
                    $property->type()->isNullable() &&
                    !$relationship->properties()->hasKey($name)
                ) {
                    return;
                }

                $data = $data->set(
                    $name,
                    $relationship->properties()->get($name)
                );
            });

        return $data;
    }
}
