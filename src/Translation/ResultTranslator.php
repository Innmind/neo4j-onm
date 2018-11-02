<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation;

use Innmind\Neo4j\ONM\{
    Translation\Result\AggregateTranslator,
    Translation\Result\RelationshipTranslator,
    Metadata\Entity,
    Metadata\Aggregate,
    Metadata\Relationship,
};
use Innmind\Neo4j\DBAL\{
    Result,
    Result\Row,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    SetInterface,
};

final class ResultTranslator
{
    private $translators;

    public function __construct(MapInterface $translators = null)
    {
        $this->translators = $translators ?? Map::of('string', EntityTranslator::class)
            (Aggregate::class, new AggregateTranslator)
            (Relationship::class, new RelationshipTranslator);

        if (
            (string) $this->translators->keyType() !== 'string' ||
            (string) $this->translators->valueType() !== EntityTranslator::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 1 must be of type MapInterface<string, %s>',
                EntityTranslator::class
            ));
        }
    }

    /**
     * Translate a raw dbal result into formated data usable for entity factories
     *
     * @param MapInterface<string, Entity> $variables Association between query variables and entity definitions
     *
     * @return MapInterface<string, SetInterface<MapInterface<string, mixed>>>
     */
    public function translate(
        Result $result,
        MapInterface $variables
    ): MapInterface {
        if (
            (string) $variables->keyType() !== 'string' ||
            (string) $variables->valueType() !== Entity::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 2 must be of type MapInterface<string, %s>',
                Entity::class
            ));
        }

        return $variables
            ->filter(static function(string $variable) use ($result): bool {
                $forVariable = $result
                    ->rows()
                    ->filter(static function(Row $row) use ($variable): bool {
                        return $row->column() === $variable;
                    });

                return $forVariable->size() > 0;
            })
            ->reduce(
                new Map('string', SetInterface::class),
                function(MapInterface $carry, string $variable, Entity $meta) use ($result): MapInterface {
                    $translator = $this->translators->get(get_class($meta));

                    return $carry->put(
                        $variable,
                        $translator->translate($variable, $meta, $result)
                    );
                }
            );
    }
}
