<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation;

use Innmind\Neo4j\ONM\{
    Translation\Specification\AggregateTranslator,
    Translation\Specification\RelationshipTranslator,
    Translation\Specification\Validator,
    Metadata\Aggregate,
    Metadata\Relationship,
    Metadata\EntityInterface,
    IdentityMatch,
    Exception\InvalidArgumentException,
    Exception\SpecificationNotApplicableException
};
use Innmind\Immutable\{
    Map,
    MapInterface
};
use Innmind\Specification\SpecificationInterface;

class SpecificationTranslator
{
    private $translators;
    private $validator;

    public function __construct(
        MapInterface $translators = null,
        Validator $validator = null
    ) {
        $this->translators = $translators ?? (new Map('string', SpecificationTranslatorInterface::class))
            ->put(Aggregate::class, new AggregateTranslator)
            ->put(Relationship::class, new RelationshipTranslator);
        $this->validator = $validator ?? new Validator;

        if (
            (string) $this->translators->keyType() !== 'string' ||
            (string) $this->translators->valueType() !== SpecificationTranslatorInterface::class
        ) {
            throw new InvalidArgumentException;
        }
    }

    public function translate(
        EntityInterface $meta,
        SpecificationInterface $specification
    ): IdentityMatch {
        if (!$this->validator->validate($specification, $meta)) {
            throw new SpecificationNotApplicableException;
        }

        return $this
            ->translators
            ->get(get_class($meta))
            ->translate($meta, $specification);
    }
}
