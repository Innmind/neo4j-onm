<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Validator;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Validator,
    Metadata\Entity,
    Metadata\Aggregate,
    Metadata\Relationship
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
            throw new \TypeError(sprintf(
                'Argument 1 must be of type MapInterface<string, %s>',
                Validator::class
            ));
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
