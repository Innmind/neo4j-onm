<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation;

use Innmind\Neo4j\ONM\{
    Translation\IdentityMatch\AggregateTranslator,
    Translation\IdentityMatch\RelationshipTranslator,
    Metadata\Aggregate,
    Metadata\Relationship,
    Metadata\EntityInterface,
    IdentityInterface,
    IdentityMatch,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\{
    Map,
    MapInterface
};

class IdentityMatchTranslator
{
    private $translators;

    public function __construct(MapInterface $translators = null)
    {
        $this->translators = $translators ?? (new Map('string', IdentityMatchTranslatorInterface::class))
            ->put(Aggregate::class, new AggregateTranslator)
            ->put(Relationship::class, new RelationshipTranslator);

        if (
            (string) $this->translators->keyType() !== 'string' ||
            (string) $this->translators->valueType() !== IdentityMatchTranslatorInterface::class
        ) {
            throw new InvalidArgumentException;
        }
    }

    public function translate(
        EntityInterface $meta,
        IdentityInterface $identity
    ): IdentityMatch {
        return $this
            ->translators
            ->get(get_class($meta))
            ->translate($meta, $identity);
    }
}