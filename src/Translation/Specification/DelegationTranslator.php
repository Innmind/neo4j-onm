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
use Innmind\Immutable\{
    MapInterface,
    Map,
};
use Innmind\Specification\SpecificationInterface;

final class DelegationTranslator implements SpecificationTranslator
{
    private $translators;
    private $validate;

    public function __construct(
        MapInterface $translators = null,
        Validator $validate = null
    ) {
        $this->translators = $translators ?? Map::of('string', SpecificationTranslator::class)
            (Aggregate::class, new AggregateTranslator)
            (Relationship::class, new RelationshipTranslator);
        $this->validate = $validate ?? new Validator\DelegationValidator;

        if (
            (string) $this->translators->keyType() !== 'string' ||
            (string) $this->translators->valueType() !== SpecificationTranslator::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 1 must be of type MapInterface<string, %s>',
                SpecificationTranslator::class
            ));
        }
    }

    public function __invoke(
        Entity $meta,
        SpecificationInterface $specification
    ): IdentityMatch {
        if (!($this->validate)($specification, $meta)) {
            throw new SpecificationNotApplicable;
        }

        $translate = $this->translators->get(get_class($meta));

        return $translate($meta, $specification);
    }
}
