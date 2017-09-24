<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Validator;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Validator,
    Metadata\Entity,
    Metadata\Aggregate,
    Metadata\Relationship,
    Exception\InvalidArgumentException
};
use Innmind\Specification\SpecificationInterface;
use Innmind\Immutable\{
    MapInterface,
    Map
};

final class DelegationValidator implements Validator
{
    private $validators;

    public function __construct(MapInterface $validators = null)
    {
        $this->validators = $validators ?? (new Map('string', Validator::class))
            ->put(Aggregate::class, new AggregateValidator)
            ->put(Relationship::class, new RelationshipValidator);

        if (
            (string) $this->validators->keyType() !== 'string' ||
            (string) $this->validators->valueType() !== Validator::class
        ) {
            throw new InvalidArgumentException;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validate(
        SpecificationInterface $specification,
        Entity $meta
    ): bool {
        return $this
            ->validators
            ->get(get_class($meta))
            ->validate($specification, $meta);
    }
}
