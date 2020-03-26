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
use Innmind\Immutable\Map;
use function Innmind\Immutable\assertMap;

final class DelegationTranslator implements MatchTranslator
{
    /** @var Map<string, MatchTranslator> */
    private Map $translators;

    /**
     * @param Map<string, MatchTranslator>|null $translators
     */
    public function __construct(Map $translators = null)
    {
        /**
         * @psalm-suppress InvalidArgument
         * @var Map<string, MatchTranslator>
         */
        $this->translators = $translators ?? Map::of('string', MatchTranslator::class)
            (Aggregate::class, new AggregateTranslator)
            (Relationship::class, new RelationshipTranslator);

        assertMap('string', MatchTranslator::class, $this->translators, 1);
    }

    public function __invoke(Entity $meta): IdentityMatch
    {
        $translate = $this->translators->get(\get_class($meta));

        return $translate($meta);
    }
}
