<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification;

use Innmind\Neo4j\ONM\{
    Translation\SpecificationTranslator,
    Metadata\Aggregate,
    Metadata\Relationship,
    Metadata\Entity,
    IdentityMatch,
    Exception\InvalidArgumentException,
    Exception\SpecificationNotApplicable
};
use Innmind\Immutable\{
    Map,
    MapInterface
};
use Innmind\Specification\SpecificationInterface;

final class DelegationTranslator implements SpecificationTranslator
{
    private $translators;
    private $validator;

    public function __construct(
        MapInterface $translators = null,
        Validator $validator = null
    ) {
        $this->translators = $translators ?? (new Map('string', SpecificationTranslator::class))
            ->put(Aggregate::class, new AggregateTranslator)
            ->put(Relationship::class, new RelationshipTranslator);
        $this->validator = $validator ?? new Validator\DelegationValidator;

        if (
            (string) $this->translators->keyType() !== 'string' ||
            (string) $this->translators->valueType() !== SpecificationTranslator::class
        ) {
            throw new InvalidArgumentException;
        }
    }

    public function translate(
        Entity $meta,
        SpecificationInterface $specification
    ): IdentityMatch {
        if (!$this->validator->validate($specification, $meta)) {
            throw new SpecificationNotApplicable;
        }

        return $this
            ->translators
            ->get(get_class($meta))
            ->translate($meta, $specification);
    }
}
