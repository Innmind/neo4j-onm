<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\EntityFactory;

use Innmind\Neo4j\ONM\{
    Translation\ResultTranslator,
    Identity\Generators,
    Metadata\Entity,
    Entity\Container,
    Entity\Container\State,
};
use Innmind\Neo4j\DBAL\Result;
use Innmind\Immutable\{
    Map,
    Set,
};
use function Innmind\Immutable\assertMap;

final class EntityFactory
{
    private ResultTranslator $translate;
    private Generators $generators;
    private Resolver $resolve;
    private Container $entities;

    public function __construct(
        ResultTranslator $translate,
        Generators $generators,
        Resolver $resolve,
        Container $entities
    ) {
        $this->translate = $translate;
        $this->generators = $generators;
        $this->resolve = $resolve;
        $this->entities = $entities;
    }

    /**
     * Translate the dbal result into a set of entities
     *
     * @param Map<string, Entity> $variables
     *
     * @return Set<object>
     */
    public function __invoke(Result $result, Map $variables): Set
    {
        assertMap('string', Entity::class, $variables, 2);

        $structuredData = ($this->translate)($result, $variables);
        $entities = Set::objects();

        /** @var Set<object> */
        return $variables
            ->filter(static function(string $variable) use ($structuredData): bool {
                return $structuredData->contains($variable);
            })
            ->reduce(
                $entities,
                function(Set $entities, string $variable, Entity $meta) use ($structuredData): Set {
                    return $structuredData
                        ->get($variable)
                        ->reduce(
                            $entities,
                            function(Set $entities, Map $data) use ($meta): Set {
                                return ($entities)(
                                    $this->makeEntity($meta, $data),
                                );
                            },
                        );
                },
            );
    }

    /**
     * @param Map<string, mixed> $data
     */
    private function makeEntity(Entity $meta, Map $data): object
    {
        $identity = $this
            ->generators
            ->get($meta->identity()->type())
            ->for(
                $data->get($meta->identity()->property()),
            );

        if ($this->entities->contains($identity)) {
            return $this->entities->get($identity);
        }

        $entity = ($this->resolve)($meta)($identity, $meta, $data);

        $this->entities = $this->entities->push(
            $identity,
            $entity,
            State::managed(),
        );

        return $entity;
    }
}
