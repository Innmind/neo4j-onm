<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Match;

use Innmind\Neo4j\ONM\{
    Translation\MatchTranslatorInterface,
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
    Str,
    Set
};

class AggregateTranslator implements MatchTranslatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function translate(EntityInterface $meta): IdentityMatch
    {
        $query = (new Query)
            ->match(
                'entity',
                $meta->labels()->toPrimitive()
            )
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
            ) {
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
            (new Map('string', EntityInterface::class))
                ->put('entity', $meta)
        );
    }
}
