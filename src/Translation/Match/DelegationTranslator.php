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

        if (
            (string) $this->translators->keyType() !== 'string' ||
            (string) $this->translators->valueType() !== MatchTranslator::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 1 must be of type Map<string, %s>',
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
