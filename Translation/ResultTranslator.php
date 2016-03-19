<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation;

use Innmind\Neo4j\ONM\{
    Metadata\EntityInterface,
    Metadata\Aggregate,
    Metadata\Relationship,
    Exception\InvalidArgumentException
};
use Innmind\Neo4j\DBAL\ResultInterface;
use Innmind\Immutable\{
    MapInterface,
    Map,
    CollectionInterface
};

class ResultTranslator
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
     * @return MapInterface<string, CollectionInterface>
     */
    public function translate(
        ResultInterface $result,
        MapInterface $variables
    ): MapInterface {
        $mapped = new Map('string', CollectionInterface::class);

        $variables->foreach(function(
            string $variable,
            EntityInterface $meta
        ) use (
            &$mapped,
            $result
        ) {
            if (!$result->rows()->hasKey($variable)) {
                return;
            }

            $translator = $this->translators->get(get_class($meta));
            $mapped = $mapped->put(
                $variable,
                $translator->translate($variable, $meta, $result)
            );
        });

        return $mapped;
    }
}
