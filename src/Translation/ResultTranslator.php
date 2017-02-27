<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation;

use Innmind\Neo4j\ONM\{
    Translation\Result\AggregateTranslator,
    Translation\Result\RelationshipTranslator,
    Metadata\EntityInterface,
    Metadata\Aggregate,
    Metadata\Relationship,
    Exception\InvalidArgumentException
};
use Innmind\Neo4j\DBAL\{
    ResultInterface,
    Result\RowInterface
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    SetInterface
};

final class ResultTranslator
{
    private $translators;

    public function __construct(MapInterface $translators = null)
    {
        $this->translators = $translators ?? (new Map('string', EntityTranslatorInterface::class))
            ->put(Aggregate::class, new AggregateTranslator)
            ->put(Relationship::class, new RelationshipTranslator);

        if (
            (string) $this->translators->keyType() !== 'string' ||
            (string) $this->translators->valueType() !== EntityTranslatorInterface::class
        ) {
            throw new InvalidArgumentException;
        }
    }

    /**
     * Translate a raw dbal result into formated data usable for entity factories
     *
     * @param ResultInterface $result
     * @param MapInterface<string, EntityInterface> $variables Association between query variables and entity definitions
     *
     * @return MapInterface<string, SetInterface<MapInterface<string, mixed>>>
     */
    public function translate(
        ResultInterface $result,
        MapInterface $variables
    ): MapInterface {
        if (
            (string) $variables->keyType() !== 'string' ||
            (string) $variables->valueType() !== EntityInterface::class
        ) {
            throw new InvalidArgumentException;
        }

        return $variables
            ->filter(function(string $variable) use ($result): bool {
                $forVariable = $result
                    ->rows()
                    ->filter(function(RowInterface $row) use ($variable): bool {
                        return $row->column() === $variable;
                    });

                return $forVariable->size() > 0;
            })
            ->reduce(
                new Map('string', SetInterface::class),
                function(Map $carry, string $variable, EntityInterface $meta) use ($result): Map {
                    $translator = $this->translators->get(get_class($meta));

                    return $carry->put(
                        $variable,
                        $translator->translate($variable, $meta, $result)
                    );
                }
            );
    }
}
