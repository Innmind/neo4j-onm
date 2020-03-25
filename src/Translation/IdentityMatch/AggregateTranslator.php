<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\IdentityMatch;

use Innmind\Neo4j\ONM\{
    Translation\IdentityMatchTranslator,
    Identity,
    Metadata\Entity,
    Metadata\Aggregate,
    Metadata\Aggregate\Child,
    IdentityMatch,
};
use Innmind\Neo4j\DBAL\Query\Query;
use Innmind\Immutable\{
    Map,
    Set,
    Str,
};
use function Innmind\Immutable\unwrap;

final class AggregateTranslator implements IdentityMatchTranslator
{
    /** @var Set<string> */
    private Set $variables;

    public function __construct()
    {
        $this->variables = Set::strings();
    }

    public function __invoke(
        Entity $meta,
        Identity $identity
    ): IdentityMatch {
        if (!$meta instanceof Aggregate) {
            throw new \TypeError('Argument 1 must be of type '.Aggregate::class);
        }

        $query = (new Query)
            ->match(
                'entity',
                ...unwrap($meta->labels()),
            )
            ->withProperty(
                $meta->identity()->property(),
                '{entity_identity}',
            )
            ->withParameter('entity_identity', $identity->value())
            ->with('entity');

        $this->variables = $this->variables->clear();
        $query = $meta->children()->reduce(
            $query,
            function(
                Query $query,
                string $property,
                Child $child
            ): Query {
                $relName = Str::of('entity_')->append($property);
                $childName = $relName
                    ->append('_')
                    ->append($child->relationship()->childProperty());
                $this->variables = ($this->variables)
                    ($relName->toString())
                    ($childName->toString());

                return $query
                    ->match('entity')
                    ->linkedTo(
                        $childName->toString(),
                        ...unwrap($child->labels()),
                    )
                    ->through(
                        $child->relationship()->type()->toString(),
                        $relName->toString(),
                        'left',
                    );
            });

        $variables = $this->variables;
        $this->variables = $this->variables->clear();

        /** @psalm-suppress InvalidArgument */
        return new IdentityMatch(
            $query->return('entity', ...unwrap($variables)),
            Map::of('string', Entity::class)
                ('entity', $meta),
        );
    }
}
