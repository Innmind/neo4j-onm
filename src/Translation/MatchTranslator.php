<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation;

use Innmind\Neo4j\ONM\{
    Translation\Match\AggregateTranslator,
    Translation\Match\RelationshipTranslator,
    Metadata\Aggregate,
    Metadata\Relationship,
    Metadata\EntityInterface,
    IdentityMatch,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\{
    Map,
    MapInterface
};

final class MatchTranslator
{
    private $translators;

    public function __construct(MapInterface $translators = null)
    {
        $this->translators = $translators ?? (new Map('string', MatchTranslatorInterface::class))
            ->put(Aggregate::class, new AggregateTranslator)
            ->put(Relationship::class, new RelationshipTranslator);

        if (
            (string) $this->translators->keyType() !== 'string' ||
            (string) $this->translators->valueType() !== MatchTranslatorInterface::class
        ) {
            throw new InvalidArgumentException;
        }
    }

    public function translate(EntityInterface $meta): IdentityMatch
    {
        return $this
            ->translators
            ->get(get_class($meta))
            ->translate($meta);
    }
}
