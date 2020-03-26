<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation;

use Innmind\Neo4j\ONM\{
    Translation\Result\AggregateTranslator,
    Translation\Result\RelationshipTranslator,
    Metadata\Entity,
    Metadata\Aggregate,
    Metadata\Relationship,
};
use Innmind\Neo4j\DBAL\{
    Result,
    Result\Row,
};
use Innmind\Immutable\{
    Map,
    Set,
};
use function Innmind\Immutable\assertMap;

final class ResultTranslator
{
    /** @var Map<string, EntityTranslator> */
    private Map $translators;

    /**
     * @param Map<string, EntityTranslator>|null $translators
     */
    public function __construct(Map $translators = null)
    {
        /**
         * @psalm-suppress InvalidArgument
         * @var Map<string, EntityTranslator>
         */
        $this->translators = $translators ?? Map::of('string', EntityTranslator::class)
            (Aggregate::class, new AggregateTranslator)
            (Relationship::class, new RelationshipTranslator);

        assertMap('string', EntityTranslator::class, $this->translators, 1);
    }

    /**
     * Translate a raw dbal result into formated data usable for entity factories
     *
     * @param Map<string, Entity> $variables Association between query variables and entity definitions
     *
     * @return Map<string, Set<Map<string, mixed>>>
     */
    public function __invoke(Result $result, Map $variables): Map
    {
        assertMap('string', Entity::class, $variables, 2);

        /** @var Map<string, Set<Map<string, mixed>>> */
        return $variables
            ->filter(static function(string $variable) use ($result): bool {
                $forVariable = $result
                    ->rows()
                    ->filter(static function(Row $row) use ($variable): bool {
                        return $row->column() === $variable;
                    });

                return !$forVariable->empty();
            })
            ->reduce(
                Map::of('string', Set::class),
                function(Map $carry, string $variable, Entity $meta) use ($result): Map {
                    $translate = $this->translators->get(get_class($meta));

                    return ($carry)(
                        $variable,
                        $translate($variable, $meta, $result),
                    );
                },
            );
    }
}
