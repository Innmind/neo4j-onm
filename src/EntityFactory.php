<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    Translation\ResultTranslator,
    Identity\Generators,
    EntityFactory\Resolver,
    Metadata\EntityInterface,
    Entity\Container,
    Exception\InvalidArgumentException
};
use Innmind\Neo4j\DBAL\ResultInterface;
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
     * @param ResultInterface $result
     * @param MapInterface<string, EntityInterface> $variables
     *
     * @return SetInterface<object>
     */
    public function make(
        ResultInterface $result,
        MapInterface $variables
    ): SetInterface {
        if (
            (string) $variables->keyType() !== 'string' ||
            (string) $variables->valueType() !== EntityInterface::class
        ) {
            throw new InvalidArgumentException;
        }

        $structuredData = $this->translator->translate($result, $variables);
        $entities = new Set('object');

        return $variables
            ->filter(function(string $variable) use ($structuredData): bool {
                return $structuredData->contains($variable);
            })
            ->reduce(
                new Set('object'),
                function(Set $carry, string $variable, EntityInterface $meta) use ($structuredData): Set {
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
    private function makeEntity(EntityInterface $meta, MapInterface $data)
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
            Container::STATE_MANAGED
        );

        return $entity;
    }
}
