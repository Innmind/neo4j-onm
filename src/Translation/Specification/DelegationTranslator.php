<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification;

use Innmind\Neo4j\ONM\{
    Translation\SpecificationTranslator,
    Metadata\Aggregate,
    Metadata\Relationship,
    Metadata\Entity,
    IdentityMatch,
    Exception\SpecificationNotApplicable,
};
use Innmind\Immutable\Map;
use function Innmind\Immutable\assertMap;
use Innmind\Specification\Specification;

final class DelegationTranslator implements SpecificationTranslator
{
    /** @var Map<string, SpecificationTranslator> */
    private Map $translators;
    private Validator $validate;

    /**
     * @param Map<string, SpecificationTranslator>|null $translators
     */
    public function __construct(
        Map $translators = null,
        Validator $validate = null
    ) {
        /**
         * @psalm-suppress InvalidArgument
         * @var Map<string, SpecificationTranslator>
         */
        $this->translators = $translators ?? Map::of('string', SpecificationTranslator::class)
            (Aggregate::class, new AggregateTranslator)
            (Relationship::class, new RelationshipTranslator);
        $this->validate = $validate ?? new Validator\DelegationValidator;

        assertMap('string', SpecificationTranslator::class, $this->translators, 1);
    }

    public function __invoke(
        Entity $meta,
        Specification $specification
    ): IdentityMatch {
        if (!($this->validate)($specification, $meta)) {
            throw new SpecificationNotApplicable;
        }

        $translate = $this->translators->get(\get_class($meta));

        return $translate($meta, $specification);
    }
}
