<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Match;

use Innmind\Neo4j\ONM\{
    Translation\MatchTranslator,
    Metadata\Aggregate,
    Metadata\Relationship,
    Metadata\Entity,
    IdentityMatch,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
};

final class DelegationTranslator implements MatchTranslator
{
    private $translators;

    public function __construct(MapInterface $translators = null)
    {
        $this->translators = $translators ?? Map::of('string', MatchTranslator::class)
            (Aggregate::class, new AggregateTranslator)
            (Relationship::class, new RelationshipTranslator);

        if (
            (string) $this->translators->keyType() !== 'string' ||
            (string) $this->translators->valueType() !== MatchTranslator::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 1 must be of type MapInterface<string, %s>',
                MatchTranslator::class
            ));
        }
    }

    public function __invoke(Entity $meta): IdentityMatch
    {
        $translate = $this->translators->get(get_class($meta));

        return $translate($meta);
    }
}
