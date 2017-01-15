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
    CollectionInterface,
    Collection
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
    ): CollectionInterface {
        if (!$meta instanceof Aggregate) {
            throw new InvalidArgumentException;
        }

        $rows = $result
            ->rows()
            ->filter(function(RowInterface $row) use ($variable) {
                return $row->column() === $variable;
            });
        $data = new Collection([]);

        foreach ($rows as $row) {
            $data = $data->push(
                $this->translateNode(
                    $row->value()[$meta->identity()->property()],
                    $meta,
                    $result
                )
            );
        }

        return $data;
    }

    private function translateNode(
        $identity,
        EntityInterface $meta,
        ResultInterface $result
    ): CollectionInterface {
        $node = $result
            ->nodes()
            ->filter(function(NodeInterface $node) use ($identity, $meta) {
                $id = $meta->identity()->property();
                $properties = $node->properties();

                return $properties->hasKey($id) &&
                    $properties->get($id) === $identity;
            })
            ->first();
        $data = (new Collection([]))
            ->set(
                $meta->identity()->property(),
                $node->properties()->get(
                    $meta->identity()->property()
                )
            );

        $meta
            ->properties()
            ->foreach(function(string $name, Property $property) use (&$data, $node) {
                if (
                    $property->type()->isNullable() &&
                    !$node->properties()->hasKey($name)
                ) {
                    return;
                }

                $data = $data->set(
                    $name,
                    $node->properties()->get($name)
                );
            });

        try {
            $meta
                ->children()
                ->foreach(function(
                    string $name,
                    ValueObject $meta
                ) use (
                    &$data,
                    $node,
                    $result
                ) {
                    $data = $data->set(
                        $name,
                        $this->translateChild($meta, $result, $node)
                    );
                });
        } catch (MoreThanOneRelationshipFoundException $e) {
            throw $e->on($meta);
        }

        return $data;
    }

    private function translateChild(
        ValueObject $meta,
        ResultInterface $result,
        NodeInterface $node
    ): CollectionInterface {
        $relMeta = $meta->relationship();
        $relationships = $result
            ->relationships()
            ->filter(function(
                RelationshipInterface $relationship
            ) use (
                $node,
                $relMeta
            ) {
                return (string) $relationship->type() === (string) $relMeta->type() &&
                    $relationship->endNode()->value() === $node->id()->value();
            });

        if ($relationships->count() > 1) {
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
    ): CollectionInterface {
        $data = new Collection([]);

        $meta
            ->relationship()
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
        $data = $data->set(
            $meta->relationship()->childProperty(),
            $this->translateValueObject(
                $meta,
                $result,
                $relationship
            )
        );

        return $data;
    }

    private function translateValueObject(
        ValueObject $meta,
        ResultInterface $result,
        RelationshipInterface $relationship
    ): CollectionInterface {
        $node = $result
            ->nodes()
            ->get(
                $relationship->startNode()->value()
            );
        $data = new Collection([]);

        $meta
            ->properties()
            ->foreach(function(
                string $name,
                Property $property
            ) use (
                &$data,
                $node
            ) {
                if (
                    $property->type()->isNullable() &&
                    !$node->properties()->hasKey($name)
                ) {
                    return;
                }

                $data = $data->set(
                    $name,
                    $node->properties()->get($name)
                );
            });

        return $data;
    }
}
