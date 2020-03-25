<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\IdentityMatch;

use Innmind\Neo4j\ONM\{
    Translation\IdentityMatchTranslator,
    Metadata\Aggregate,
    Metadata\Relationship,
    Metadata\Entity,
    Identity,
    IdentityMatch,
};
use Innmind\Immutable\Map;
use function Innmind\Immutable\assertMap;

final class DelegationTranslator implements IdentityMatchTranslator
{
    /** @var Map<string, IdentityMatchTranslator> */
    private Map $translators;

    /**
     * @param Map<string, IdentityMatchTranslator>|null $translators
     */
    public function __construct(Map $translators = null)
    {
        /**
         * @psalm-suppress InvalidArgument
         * @var Map<string, IdentityMatchTranslator>
         */
        $this->translators = $translators ?? Map::of('string', IdentityMatchTranslator::class)
            (Aggregate::class, new AggregateTranslator)
            (Relationship::class, new RelationshipTranslator);

        assertMap('string', IdentityMatchTranslator::class, $this->translators, 1);
    }

    public function __invoke(
        Entity $meta,
        Identity $identity
    ): IdentityMatch {
        $translate = $this->translators->get(\get_class($meta));

        return $translate($meta, $identity);
    }
}
