<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\EntityFactory;

use Innmind\Neo4j\ONM\{
    Translation\ResultTranslator,
    Identity\Generators,
    EntityFactory\Resolver,
    Metadata\Entity,
    Entity\Container,
    Entity\Container\State
};
use Innmind\Neo4j\DBAL\Result;
use Innmind\Immutable\{
    Map,
    Set,
    SetInterface,
    MapInterface
};

final class EntityFactory
{
    private $translator;
    private $generators;
    private $resolver;
    private $entities;

    public function __construct(
        ResultTranslator $translator,
        Generators $generators,
        Resolver $resolver,
        Container $entities
    ) {
        $this->translator = $translator;
        $this->generators = $generators;
        $this->resolver = $resolver;
        $this->entities = $entities;
    }

    /**
     * Translate the dbal result into a set of entities
     *
     * @param MapInterface<string, Entity> $variables
     *
     * @return SetInterface<object>
     */
    public function make(
        Result $result,
        MapInterface $variables
    ): SetInterface {
        if (
            (string) $variables->keyType() !== 'string' ||
            (string) $variables->valueType() !== Entity::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 2 must be of type MapInterface<string, %s>',
                Entity::class
            ));
        }

        $structuredData = $this->translator->translate($result, $variables);
        $entities = new Set('object');

        return $variables
            ->filter(function(string $variable) use ($structuredData): bool {
                return $structuredData->contains($variable);
            })
            ->reduce(
                new Set('object'),
                function(Set $carry, string $variable, Entity $meta) use ($structuredData): Set {
                    return $structuredData
                        ->get($variable)
                        ->reduce(
                            $carry,
                            function(Set $carry, MapInterface $data) use ($meta): Set {
                                return $carry->add(
                                    $this->makeEntity($meta, $data)
                                );
                            }
                        );
                }
            );
    }

    /**
     * @param MapInterface<string, mixed> $data
     */
    private function makeEntity(Entity $meta, MapInterface $data)
    {
        $identity = $this
            ->generators
            ->get($meta->identity()->type())
            ->for(
                $data->get($meta->identity()->property())
            );

        if ($this->entities->contains($identity)) {
            return $this->entities->get($identity);
        }

        $entity = $this
            ->resolver
            ->get($meta)
            ->make($identity, $meta, $data);
        $this->entities = $this->entities->push(
            $identity,
            $entity,
            State::managed()
        );

        return $entity;
    }
}
