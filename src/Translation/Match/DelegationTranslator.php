<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Match;

use Innmind\Neo4j\ONM\{
    Translation\MatchTranslator,
    Metadata\Aggregate,
    Metadata\Relationship,
    Metadata\Entity,
    IdentityMatch,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\{
    Map,
    MapInterface
};

final class DelegationTranslator implements MatchTranslator
{
    private $translators;

    public function __construct(MapInterface $translators = null)
    {
        $this->translators = $translators ?? (new Map('string', MatchTranslator::class))
            ->put(Aggregate::class, new AggregateTranslator)
            ->put(Relationship::class, new RelationshipTranslator);

        if (
            (string) $this->translators->keyType() !== 'string' ||
            (string) $this->translators->valueType() !== MatchTranslator::class
        ) {
            throw new InvalidArgumentException;
        }
    }

    public function translate(Entity $meta): IdentityMatch
    {
        return $this
            ->translators
            ->get(get_class($meta))
            ->translate($meta);
    }
}
