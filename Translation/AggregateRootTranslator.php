<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation;

use Innmind\Neo4j\ONM\{
    Metadata\EntityInterface,
    Metadata\AggregateRoot,
    Metadata\ValueObject,
    Metadata\Property,
    Exception\InvalidArgumentException,
    Exception\MoreThanOneRelationshipFoundException
};
use Innmind\Neo4j\DBAL\{
    ResultInterface,
    Result\NodeInterface,
    Result\RelationshipInterface
};
use Innmind\Immutable\{
    CollectionInterface,
    Collection
};

class AggregateRootTranslator implements EntityTranslatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function translate(
        string $variable,
        EntityInterface $meta,
        ResultInterface $result
    ): CollectionInterface {
        if (!$meta instanceof AggregateRoot) {
            throw new InvalidArgumentException;
        }

        $row = $result->rows()->get($variable);

        if (isset($row->value()[0])) { // means collections of nodes
            $data = new Collection([]);

            foreach ($row->value() as $node) {
                $data = $data->push(
                    $this->translateNode(
                        $node[$meta->identity()->property()],
                        $meta,
                        $result
                    )
                );
            }

            return $data;
        }

        return $this->translateNode(
            $row->value()[$meta->identity()->property()],
            $meta,
            $result
        );
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

        if (
            !$relMeta->isCollection() &&
            $relationships->count() > 1
        ) {
            throw MoreThanOneRelationshipFoundException::for($meta);
        }

        if ($relMeta->isCollection()) {
            $data = new Collection([]);

            $relationships->each(function(
                int $index,
                RelationshipInterface $relationship
            ) use (
                &$data,
                $meta,
                $result
            ) {
                $data = $data->push(
                    $this->translateRelationship(
                        $meta,
                        $result,
                        $relationship
                    )
                );
            });

            return $data;
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
