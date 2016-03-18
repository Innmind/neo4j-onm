<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation;

use Innmind\Neo4j\ONM\{
    IdentityInterface,
    Metadata\EntityInterface,
    Metadata\ValueObject,
    IdentityMatch
};
use Innmind\Neo4j\DBAL\{
    Query,
    Clause\Expression\Relationship
};
use Innmind\Immutable\{
    Map,
    StringPrimitive as Str
};

class AggregateMatchTranslator implements IdentityMatchTranslatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function translate(
        EntityInterface $meta,
        IdentityInterface $identity
    ): IdentityMatch {
        $query = (new Query)
            ->match(
                'entity',
                $meta->labels()->toPrimitive()
            )
            ->withProperty(
                $meta->identity()->property(),
                '{entity_identity}'
            )
            ->withParameter('entity_identity', $identity->value())
            ->with('entity');

        $meta
            ->children()
            ->foreach(function(
                string $property,
                ValueObject $child
            ) use (
                &$query
            ) {
                $name = (new Str('entity_'))->append($property);
                $query = $query
                    ->match('entity')
                    ->linkedTo(
                        (string) $name
                            ->append('_')
                            ->append($child->relationship()->childProperty()),
                        $child->labels()->toPrimitive()
                    )
                    ->through(
                        (string) $child->relationship()->type(),
                        (string) $name,
                        Relationship::LEFT
                    );
            });


        return new IdentityMatch(
            $query->return('entity'),
            (new Map('string', EntityInterface::class))
                ->put('entity', $meta)
        );
    }
}
