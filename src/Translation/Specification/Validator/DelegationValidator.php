<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Validator;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Validator,
    Metadata\Entity,
    Metadata\Aggregate,
    Metadata\Relationship,
};
use Innmind\Specification\Specification;
use Innmind\Immutable\Map;

final class DelegationValidator implements Validator
{
    /** @var Map<string, Validator> */
    private Map $validators;

    /**
     * @param Map<string, Validator>|null $validators
     */
    public function __construct(Map $validators = null)
    {
        /**
         * @psalm-suppress InvalidArgument
         * @var Map<string, Validator>
         */
        $this->validators = $validators ?? Map::of('string', Validator::class)
            (Aggregate::class, new AggregateValidator)
            (Relationship::class, new RelationshipValidator);

        if (
            (string) $this->validators->keyType() !== 'string' ||
            (string) $this->validators->valueType() !== Validator::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 1 must be of type Map<string, %s>',
                Validator::class
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(
        Specification $specification,
        Entity $meta
    ): bool {
        $validate = $this->validators->get(get_class($meta));

        return $validate($specification, $meta);
    }
}
