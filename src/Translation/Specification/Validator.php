<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Validator\AggregateValidator,
    Translation\Specification\Validator\RelationshipValidator,
    Metadata\EntityInterface,
    Metadata\Aggregate,
    Metadata\Relationship,
    Exception\InvalidArgumentException
};
use Innmind\Specification\SpecificationInterface;
use Innmind\Immutable\{
    MapInterface,
    Map
};

class Validator implements ValidatorInterface
{
    private $validators;

    public function __construct(MapInterface $validators = null)
    {
        $this->validators = $validators ?? (new Map('string', ValidatorInterface::class))
            ->put(Aggregate::class, new AggregateValidator)
            ->put(Relationship::class, new RelationshipValidator);

        if (
            (string) $this->validators->keyType() !== 'string' ||
            (string) $this->validators->valueType() !== ValidatorInterface::class
        ) {
            throw new InvalidArgumentException;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validate(
        SpecificationInterface $specification,
        EntityInterface $meta
    ): bool {
        return $this
            ->validators
            ->get(get_class($meta))
            ->validate($specification, $meta);
    }
}
