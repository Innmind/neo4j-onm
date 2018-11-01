<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\IdentityMatch;

use Innmind\Neo4j\ONM\{
    Translation\IdentityMatchTranslator,
    Identity,
    Metadata\Entity,
    Metadata\ValueObject,
    IdentityMatch
};
use Innmind\Neo4j\DBAL\{
    Query\Query,
    Clause\Expression\Relationship
};
use Innmind\Immutable\{
    Map,
    Str,
    Set
};

final class AggregateTranslator implements IdentityMatchTranslator
{
    /**
     * {@inheritdoc}
     */
    public function translate(
        Entity $meta,
        Identity $identity
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

        $variables = new Set('string');
        $meta
            ->children()
            ->foreach(function(
                string $property,
                ValueObject $child
            ) use (
                &$query,
                &$variables
            ): void {
                $relName = (new Str('entity_'))->append($property);
                $childName = $relName
                    ->append('_')
                    ->append($child->relationship()->childProperty());
                $variables = $variables
                    ->add((string) $relName)
                    ->add((string) $childName);

                $query = $query
                    ->match('entity')
                    ->linkedTo(
                        (string) $childName,
                        $child->labels()->toPrimitive()
                    )
                    ->through(
                        (string) $child->relationship()->type(),
                        (string) $relName,
                        Relationship::LEFT
                    );
            });


        return new IdentityMatch(
            $query->return('entity', ...$variables->toPrimitive()),
            (new Map('string', Entity::class))
                ->put('entity', $meta)
        );
    }
}
