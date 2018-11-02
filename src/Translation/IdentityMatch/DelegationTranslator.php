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
use Innmind\Immutable\{
    MapInterface,
    Map,
};

final class DelegationTranslator implements IdentityMatchTranslator
{
    private $translators;

    public function __construct(MapInterface $translators = null)
    {
        $this->translators = $translators ?? Map::of('string', IdentityMatchTranslator::class)
            (Aggregate::class, new AggregateTranslator)
            (Relationship::class, new RelationshipTranslator);

        if (
            (string) $this->translators->keyType() !== 'string' ||
            (string) $this->translators->valueType() !== IdentityMatchTranslator::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 1 must be of type MapInterface<string, %s>',
                IdentityMatchTranslator::class
            ));
        }
    }

    public function translate(
        Entity $meta,
        Identity $identity
    ): IdentityMatch {
        return $this
            ->translators
            ->get(get_class($meta))
            ->translate($meta, $identity);
    }
}
